<?php
declare(strict_types=1);
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Services\UniversalToolProvider;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\ToolCard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class CorporateToolsNavigationTest extends TestCase
{
    public function testProviderReturnsExactlyFourDeterministicDisabledCards():void
    {
        $provider=new CorporateToolsProvider();$first=$provider->tools();
        self::assertCount(4,$first);self::assertEquals($first,$provider->tools());
        self::assertSame(['finance','human-resources','marketing','admin'],array_map(fn(ToolCard $card)=>$card->key,$first));
        self::assertSame(['Finance','Human Resources','Marketing','Admin'],array_map(fn(ToolCard $card)=>$card->title,$first));
        self::assertCount(4,array_unique(array_map(fn(ToolCard $card)=>$card->key,$first)));
        foreach($first as $index=>$card){self::assertFalse($card->enabled);self::assertNull($card->route);self::assertNotSame('',$card->description);self::assertSame(($index+1)*10,$card->sortOrder);}
        self::assertCount(0,(new ReflectionClass($provider))->getConstructor()?->getParameters()??[]);
    }
    #[DataProvider('accessCases')]
    public function testCorporateAccessFrameworkAloneFiltersPresentation(string $class,string $jobTitle,string $username,array $configured,int $expected):void
    {
        $user=$this->user($class,$username,$jobTitle);$home=$this->home($configured)->forRequest(new AuthenticatedRequestContext($user,'TEST-token'));
        self::assertCount(12,$home->universalTools);self::assertSame($expected===4,$home->showCorporateTools);self::assertCount($expected,$home->corporateTools);self::assertTrue($home->setupSummary->initialSetup);
    }
    public static function accessCases():iterable
    {
        yield 'listed corporate'=>['corporate','Analyst','listeduser',['finance'=>['listeduser']],4];
        yield 'listed manager'=>['manager','Property Manager','listedmanager',['admin'=>['listedmanager']],4];
        yield 'class alone does not grant'=>['corporate','Executive','unlisteduser',['finance'=>['otheruser']],0];
        yield 'title does not grant'=>['maintenance','Corporate Administrator','unlisteduser',['finance'=>['otheruser']],0];
    }
    public function testContactEmailAndSharedMailboxCannotGrantAccess():void
    {
        $service=new CorporateAccessService(['finance'=>['manager@property.example.test']]);
        $user=$this->user('manager','permanentmanager','Property Manager');
        self::assertFalse($service->allows(new AuthenticatedRequestContext($user,'TEST-token')));
        self::assertFalse(property_exists($user,'businessEmail'));self::assertFalse(property_exists($user,'personalEmail'));
        $listed=new CorporateAccessService(['finance'=>['permanentmanager']]);
        self::assertTrue($listed->allows(new AuthenticatedRequestContext($user,'TEST-token')));
    }
    public function testAccessServiceFailsClosedOutsideAuthenticatedContext():void
    {
        $service=new CorporateAccessService(['admin'=>['disableduser','nonexistentuser']]);
        self::assertFalse($service->allows(null));
    }
    public function testConfiguredPermanentUsernameChuckReceivesCorporateTools():void
    {
        $config=require dirname(__DIR__,2).'/config/corporate-access.php';
        self::assertContains('chuck',$config['administration']);
        $context=new AuthenticatedRequestContext($this->user('manager','chuck','Any Job Title'),'TEST-token');
        self::assertTrue((new CorporateAccessService($config))->allows($context));
        $home=$this->home($config)->forRequest($context);
        self::assertTrue($home->showCorporateTools);self::assertCount(4,$home->corporateTools);
    }
    public function testCorporateDashboardRendersSectionAndDisabledDropdownInOrder():void
    {
        $html=$this->render('manager','listedmanager',['finance'=>['listedmanager']]);
        self::assertSame(1,substr_count($html,'id="corporate-tools-title"'));
        foreach(['Finance','Human Resources','Marketing','Admin','Frequently used functions for corporate staff.','aria-label="Corporate tools menu"'] as $value)self::assertStringContainsString($value,$html);
        self::assertSame(4,substr_count($html,'gateway-navbar__disabled-item'));
        self::assertLessThan(strpos($html,'id="corporate-tools-title"'),strpos($html,'id="universal-tools-title"'));
        self::assertLessThan(strpos($html,'id="gateway-setup-title"'),strpos($html,'id="corporate-tools-title"'));
        $section=substr($html,(int)strpos($html,'id="corporate-tools"'),(int)strpos($html,'id="gateway-setup-title"')-(int)strpos($html,'id="corporate-tools"'));
        self::assertStringNotContainsString('<a ',$section);self::assertStringNotContainsString('href=',$section);
    }
    public function testNonCorporateDashboardOmitsSectionAndDropdown():void
    {
        $html=$this->render('corporate','unlisteduser',['finance'=>['otheruser']]);
        self::assertStringNotContainsString('Corporate Tools',$html);self::assertStringNotContainsString('Corporate tools menu',$html);
        self::assertStringContainsString('Universal Tools',$html);self::assertStringContainsString('Gateway Setup',$html);
    }
    public function testArchitectureKeepsFilteringAndDefinitionsOutOfPresentation():void
    {
        $root=dirname(__DIR__,2);$provider=file_get_contents($root.'/app/Services/CorporateToolsProvider.php');$service=file_get_contents($root.'/app/Services/DashboardHomeService.php');$controller=file_get_contents($root.'/app/Http/Controllers/DashboardController.php');$view=file_get_contents($root.'/resources/views/dashboard.php');
        self::assertDoesNotMatchRegularExpression('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b/i',$provider);self::assertStringNotContainsString('Repositories\\',$provider);
        self::assertStringContainsString('$this->corporateAccess->allows($context)',$service);self::assertStringNotContainsString('jobTitle===',$service);self::assertStringNotContainsString("employeeClass==='corporate'",$service);
        foreach(['Finance','Human Resources','Marketing'] as $definition){self::assertStringNotContainsString($definition,$controller);self::assertStringNotContainsString($definition,$view);}
        self::assertStringNotContainsString('corporate-access.php',$controller);self::assertStringNotContainsString('corporate-access.php',$view);self::assertStringNotContainsString('->employeeClass===',$view);self::assertFileDoesNotExist($root.'/database/migrations/202607270003_corporate_tools.php');
        self::assertSame([],glob($root.'/public/assets/js/*.js')?:[]);
    }
    /** @param array<string,list<string>> $access */
    private function home(array $access=[]):DashboardHomeService
    {
        $store=new class implements DashboardSummaryStoreInterface{public function counts():array{return ['property_count'=>0,'employee_count'=>1,'user_count'=>1,'active_user_count'=>1,'active_assignment_count'=>0];}};
        return new DashboardHomeService(new DashboardSummaryService($store),new UniversalToolProvider(),new CorporateToolsProvider(),new CorporateAccessService($access));
    }
    /** @param array<string,list<string>> $access */
    private function render(string $class,string $username,array $access):string
    {
        $state=[];$controller=new DashboardController(new CsrfService($state),$this->home($access),dirname(__DIR__,2).'/resources/views');
        return $controller->index(new AuthenticatedRequestContext($this->user($class,$username,'Same Job'),'TEST-session-secret'))->body;
    }
    private function user(string $class,string $username,string $jobTitle):AuthenticatedUser
    {
        return new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),$username,'Test Employee',$jobTitle,$class);
    }
}
