<?php
declare(strict_types=1);
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Services\UniversalToolProvider;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class CorporateToolsNavigationTest extends TestCase
{
    #[DataProvider('accessCases')]
    public function testCategoryAccessUsesOnlyValidatedPermanentUsername(?AuthenticatedRequestContext $context,string $category,bool $expected):void
    {
        $service=$this->access(['finance']);self::assertSame($expected,$service->canAccessCategory($context,$category));
    }
    public static function accessCases():iterable
    {
        yield 'listed normalized'=>[self::contextFor('LISTEDUSER','maintenance','Unrelated title'),' FINANCE ',true];yield 'null'=>[null,'finance',false];yield 'unknown'=>[self::contextFor('listeduser'),'unknown',false];yield 'invalid category'=>[self::contextFor('listeduser'),'../finance',false];
    }
    public function testProviderAlwaysReturnsAllCategoriesWithAccessAndAvailabilityStates():void
    {
        $access=$this->access(['human-resources','credit-cards']);$cards=(new CorporateToolsProvider($access))->tools(self::contextFor('hruser'));self::assertSame(['operations','human-resources','company-notices','application-reviews','finance','marketing','admin','credit-cards'],array_map(static fn($card)=>$card->key,$cards));self::assertCount(8,$cards);
        self::assertTrue($cards[1]->enabled);self::assertSame('/human-resources',$cards[1]->route);self::assertSame('Open Human Resources',$cards[1]->footerLabel);
        self::assertFalse($cards[7]->enabled);self::assertNull($cards[7]->route);self::assertSame('Module planned',$cards[7]->footerLabel);
        foreach([0,2,3,4,5,6] as $index){self::assertFalse($cards[$index]->enabled);self::assertNull($cards[$index]->route);self::assertSame('Access not assigned',$cards[$index]->footerLabel);}
    }
    public function testEveryAuthenticatedUserSeesCardsAndNavbarWithoutUnsafeDestinations():void
    {
        $html=$this->render('unlisted',[]);foreach(['Corporate Tools','Operations','Finance','Human Resources','Company Notices','Marketing','Admin','Credit Cards','Access not assigned','aria-disabled="true"'] as $text)self::assertStringContainsString($text,$html);self::assertStringNotContainsString('href="#"',$html);self::assertStringNotContainsString('javascript:void',$html);self::assertStringNotContainsString('corporate-access.php',$html);
        $corporate=substr($html,(int)strpos($html,'id="corporate-tools"'),(int)strpos($html,'id="gateway-setup-title"')-(int)strpos($html,'id="corporate-tools"'));self::assertSame(8,substr_count($corporate,'data-tool-key='));self::assertStringNotContainsString('<a ',$corporate);
    }
    public function testTimConfigurationGrantsEveryCategoryAndOnlyImplementedHrLinks():void
    {
        $categories=['finance','human-resources','marketing','credit-cards'];$access=$this->access($categories);foreach($categories as $category)self::assertTrue($access->canAccessCategory(self::contextFor('tim'),$category));self::assertFalse($access->canAccessCategory(self::contextFor('tim'),'admin'));$html=$this->render('tim',$categories);self::assertSame(2,substr_count($html,'href="/human-resources"'));self::assertStringContainsString('Open Human Resources',$html);
    }
    public function testViewsNeverInspectIdentityOrConfiguration():void
    {
        $root=dirname(__DIR__,2);$views=(string)file_get_contents($root.'/resources/views/dashboard.php').(string)file_get_contents($root.'/resources/views/components/navbar.php');foreach(['corporate-access.php','businessEmail','personalEmail','canAccessCategory','CorporateAccessService'] as $forbidden)self::assertStringNotContainsString($forbidden,$views);self::assertFileDoesNotExist($root.'/database/migrations/202608010007_corporate_access.php');
    }
    private function render(string $username,array $categories):string
    {
        $state=[];$service=$this->access($categories);$home=new DashboardHomeService(new DashboardSummaryService(new class implements DashboardSummaryStoreInterface{public function counts():array{return ['property_count'=>0,'employee_count'=>1,'user_count'=>1,'active_user_count'=>1,'active_assignment_count'=>0];}}),new UniversalToolProvider(),new CorporateToolsProvider($service),$service);return (new DashboardController(new CsrfService($state),$home,dirname(__DIR__,2).'/resources/views'))->index(self::contextFor($username))->body;
    }
    private function access(array $allowed):CorporateAccessService{$store=new class($allowed) implements CategoryAccessStoreInterface{public function __construct(private array $allowed){}public function hasEffectiveMembership(int $userId,string $category):bool{return in_array($category,$this->allowed,true);}public function findUserByUsername(string $username):?array{return null;}public function allUsers():array{return [];}public function memberships():array{return [];}public function grant(array $membership):void{}public function revoke(int $userId,string $category):void{}};$config=require dirname(__DIR__,2).'/config/corporate-access.php';return new CorporateAccessService($store,$config['categories']);}
    private static function contextFor(string $username,string $class='manager',string $title='Any title'):AuthenticatedRequestContext{return new AuthenticatedRequestContext(new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),$username,'Test User',$title,$class),'TEST-token');}
}
