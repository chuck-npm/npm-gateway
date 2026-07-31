<?php
declare(strict_types=1);
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\HumanResourcesController;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;
final class HumanResourcesWorkspaceTest extends TestCase
{
    public function testAuthorizedLandingHasExactlyThreeOrderedCardsAndNoAppliances():void
    {
        $session=[];$controller=new HumanResourcesController(new CorporateAccessService(['administration'=>['listed']]),new CorporateToolsProvider(),new CsrfService($session),dirname(__DIR__,2).'/resources/views');$response=$controller->index($this->context('listed'));self::assertSame(200,$response->status);self::assertSame(3,substr_count($response->body,'data-tool-key='));$employees=strpos($response->body,'data-tool-key="employees"');$properties=strpos($response->body,'data-tool-key="properties"');$cards=strpos($response->body,'data-tool-key="credit-cards"');self::assertLessThan($properties,$employees);self::assertLessThan($cards,$properties);self::assertStringContainsString('href="/employees"',$response->body);self::assertStringContainsString('href="/human-resources/properties"',$response->body);self::assertStringNotContainsString('Appliances',$response->body);$credit=substr($response->body,(int)$cards,800);self::assertStringNotContainsString('href=',$credit);
    }
    public function testEmployeeClassAndTitleDoNotBypassExplicitAccess():void
    {
        $session=[];$controller=new HumanResourcesController(new CorporateAccessService(['administration'=>['other']]),new CorporateToolsProvider(),new CsrfService($session),dirname(__DIR__,2).'/resources/views');self::assertSame(403,$controller->index($this->context('unlisted'))->status);
    }
    private function context(string $username):AuthenticatedRequestContext{return new AuthenticatedRequestContext(new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),$username,'Test User','HR Director','corporate'),'token');}
}
