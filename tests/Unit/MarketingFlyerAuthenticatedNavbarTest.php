<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;

use DOMDocument;
use DOMXPath;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\ToolCard;
use PHPUnit\Framework\TestCase;

final class MarketingFlyerAuthenticatedNavbarTest extends TestCase
{
    private string $views;
    private AuthenticatedUser $user;

    protected function setUp():void
    {
        $this->views=dirname(__DIR__,2).'/resources/views';
        $this->user=new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'chuck','Chuck Lundquist');
    }

    public function testMarketingLandingUsesStandardAuthenticatedNavbar():void
    {
        $cards=[];$user=$this->user;$logoutCsrfToken='logout-token';$navbarCorporateItems=$this->corporateItems();
        ob_start();require $this->views.'/corporate/marketing/index.php';$html=(string)ob_get_clean();
        $this->assertAuthenticatedNavbar($html);
    }

    /** @dataProvider flyerPages */
    public function testEveryFlyerPageUsesStandardAuthenticatedNavbarAndKeepsContentAndBreadcrumbs(string $view,string $content,string $current):void
    {
        $html=$this->renderFlyer($view);
        $this->assertAuthenticatedNavbar($html);
        self::assertStringContainsString($content,$html);
        $xpath=$this->xpath($html);
        self::assertSame(1,$xpath->query('//nav[@aria-label="Breadcrumb"]//li[contains(@class,"active") and normalize-space()="'.$current.'"]')->length);
    }

    public static function flyerPages():array
    {
        return [['index','Marketing Flyers','Flyers'],['form','Upload Flyer','New'],['show','Flyer Details','Flyer'],['replace','Replacement File','Replace']];
    }

    public function testFlyerControllerBuildsNavbarFromSharedAuthenticatedContext():void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Http/Controllers/MarketingFlyerController.php');
        self::assertStringContainsString('CorporateToolsProviderInterface$tools',$source);
        self::assertStringContainsString('$navbarCorporateItems=$this->tools->tools($c)',$source);
        self::assertStringContainsString('$logoutCsrfToken=$csrfToken',$source);
        self::assertStringContainsString("return\$this->render('index',\$c",$source);
        foreach(['index.php','form.php','show.php','replace.php']as$file){$view=(string)file_get_contents($this->views.'/corporate/marketing/flyers/'.$file);self::assertStringNotContainsString('Dashboard</a>',$view);self::assertStringNotContainsString('Notifications</a>',$view);}
    }

    private function renderFlyer(string $view):string
    {
        $user=$this->user;$logoutCsrfToken='logout-token';$navbarCorporateItems=$this->corporateItems();$csrfToken='csrf';$success='';$warning='';$error='';
        $properties=[['public_id'=>'PROPERTYPUBLICID00000000001','display_name'=>'Pine Hill','property_code'=>'PH']];$months=['2026-08'=>'August 2026'];$month='2026-08';$property='';$type='standard';
        $flyer=['public_id'=>'FLYERPUBLICID0000000000001','property_name'=>'Pine Hill','property_public_id'=>$properties[0]['public_id'],'flyer_month'=>'2026-08-01','flyer_type'=>'standard','uploaded_by_name'=>'Chuck Lundquist','uploaded_at'=>'2026-08-14 12:00:00'];$flyers=[$flyer];
        ob_start();require $this->views.'/corporate/marketing/flyers/'.$view.'.php';return(string)ob_get_clean();
    }

    private function corporateItems():array
    {
        return [new ToolCard('marketing','Marketing','Marketing resources.','Corporate','Open Marketing','/corporate/marketing',true,10,null,null,'corporate.marketing')];
    }

    private function assertAuthenticatedNavbar(string $html):void
    {
        $xpath=$this->xpath($html);
        self::assertSame(1,$xpath->query('//nav[@aria-label="Primary navigation"]')->length);
        foreach(['Dashboard','Notifications']as$label)self::assertSame(1,$xpath->query('//nav[@aria-label="Primary navigation"]//a[normalize-space()="'.$label.'"]')->length);
        self::assertSame(1,$xpath->query('//nav[@aria-label="Primary navigation"]//button[normalize-space()="Corporate"]')->length);
        self::assertSame(1,$xpath->query('//nav[@aria-label="Primary navigation"]//button[normalize-space()="Chuck Lundquist"]')->length);
        self::assertSame(0,$xpath->query('//nav[@aria-label="Primary navigation"]//button[normalize-space()="User menu"]')->length);
        self::assertSame(1,$xpath->query('//nav[@aria-label="Primary navigation"]//form[@action="/logout"]')->length);
    }

    private function xpath(string $html):DOMXPath
    {
        $dom=new DOMDocument();@$dom->loadHTML($html);return new DOMXPath($dom);
    }
}
