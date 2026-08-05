<?php
declare(strict_types=1);
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Http\Controllers\HumanResourcesController;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;
final class HumanResourcesWorkspaceTest extends TestCase
{
    public function testAuthorizedLandingHasOrderedCardsIncludingEmergencyContacts():void
    {
        $session=[];$access=$this->access(['human-resources']);$controller=new HumanResourcesController($access,new CorporateToolsProvider($access),new CsrfService($session),dirname(__DIR__,2).'/resources/views');$response=$controller->index($this->context('listed'));self::assertSame(200,$response->status);self::assertSame(4,substr_count($response->body,'data-tool-key='));$employees=strpos($response->body,'data-tool-key="employees"');$properties=strpos($response->body,'data-tool-key="properties"');$eci=strpos($response->body,'data-tool-key="emergency-contacts"');$cards=strpos($response->body,'data-tool-key="credit-cards"');self::assertLessThan($properties,$employees);self::assertLessThan($eci,$properties);self::assertLessThan($cards,$eci);self::assertStringContainsString('href="/human-resources/employees"',$response->body);self::assertStringContainsString('href="/human-resources/properties"',$response->body);self::assertStringContainsString('href="/corporate/human-resources/emergency-contacts"',$response->body);self::assertStringNotContainsString('Appliances',$response->body);$credit=substr($response->body,(int)$cards,800);self::assertStringNotContainsString('href=',$credit);
    }
    public function testEmployeeClassAndTitleDoNotBypassExplicitHrAccess():void
    {
        $session=[];$access=$this->access([]);$controller=new HumanResourcesController($access,new CorporateToolsProvider($access),new CsrfService($session),dirname(__DIR__,2).'/resources/views');self::assertSame(403,$controller->index($this->context('unlisted'))->status);
    }
    public function testFinanceOnlyMembershipDoesNotGrantHumanResources():void
    {
        $session=[];$access=$this->access(['finance']);$controller=new HumanResourcesController($access,new CorporateToolsProvider($access),new CsrfService($session),dirname(__DIR__,2).'/resources/views');self::assertSame(403,$controller->index($this->context('listed'))->status);
    }
    public function testConfirmedTimUsernameCanAccessHumanResourcesDirectly():void
    {
        $session=[];$config=require dirname(__DIR__,2).'/config/corporate-access.php';$access=$this->access(['human-resources']);$controller=new HumanResourcesController($access,new CorporateToolsProvider($access),new CsrfService($session),dirname(__DIR__,2).'/resources/views');self::assertSame(200,$controller->index($this->context('tim'))->status);self::assertArrayNotHasKey('tim',$config);
    }
    private function context(string $username):AuthenticatedRequestContext{return new AuthenticatedRequestContext(new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),$username,'Test User','HR Director','corporate'),'token');}
    private function access(array $allowed):CorporateAccessService{$store=new class($allowed) implements CategoryAccessStoreInterface{public function __construct(private array $allowed){}public function hasEffectiveMembership(int $userId,string $category):bool{return in_array($category,$this->allowed,true);}public function findUserByUsername(string $username):?array{return null;}public function allUsers():array{return [];}public function memberships():array{return [];}public function grant(array $membership):void{}public function revoke(int $userId,string $category):void{}};$config=require dirname(__DIR__,2).'/config/corporate-access.php';return new CorporateAccessService($store,$config['categories']);}
}
