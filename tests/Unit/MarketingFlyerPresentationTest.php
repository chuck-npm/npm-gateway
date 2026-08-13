<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use PHPUnit\Framework\TestCase;

final class MarketingFlyerPresentationTest extends TestCase
{
    private string $root;
    protected function setUp():void{$this->root=dirname(__DIR__,2);}
    private function view(string $name):string{return(string)file_get_contents($this->root.'/resources/views/corporate/marketing/flyers/'.$name.'.php');}

    public function testNewFlyerUsesGatewayFormStructureAndDesktopGrid():void
    {
        $view=$this->view('form');
        foreach(['breadcrumb.php','gateway-card gateway-property-form-card gateway-flyer-form','gateway-card__body','row gateway-form-grid','col-12 col-lg-4','col-12 col-sm-6 col-lg-3','col-12 col-sm-6 col-lg-2','form-label','form-select','form-control','name="property"','name="month"','name="type"','name="flyer"','gateway-button--primary','Upload Flyer','gateway-button--secondary','>Cancel</a>','gateway-flyer-progress','gateway-flyer-progress__bar','gateway-flyer-progress__status']as$value)self::assertStringContainsString($value,$view);
        self::assertStringNotContainsString('<button type="submit">',$view);
        self::assertStringNotContainsString('style=',$view);
        foreach(['gateway-flyer-stage','data-flyer-stage','Uploading file','Processing &amp; saving flyer','Complete']as$value)self::assertStringNotContainsString($value,$view);
        foreach(['gateway-flyer-progress__track','gateway-flyer-progress__bar','role="progressbar"','aria-valuemin="0"','aria-valuemax="100"','aria-valuenow="0"','aria-live="polite"','Uploading flyer... 0%']as$value)self::assertStringContainsString($value,$view);
        self::assertStringNotContainsString('<progress',$view);
    }

    public function testLibraryUsesGatewayFiltersTableAndEmptyState():void
    {
        $view=$this->view('index');
        foreach(['breadcrumb.php','page-header.php','gateway-card gateway-flyer-filters','row gateway-form-grid','gateway-directory-table-wrap','table gateway-directory-table','gateway-flyer-row-actions','gateway-empty-state','No flyers found for','Upload Flyer','gateway-link-button--danger']as$value)self::assertStringContainsString($value,$view);
        self::assertStringNotContainsString('thumbnail',$view);
    }

    public function testDetailAndReplaceUseCoherentGatewaySurfaces():void
    {
        $show=$this->view('show');$replace=$this->view('replace');
        foreach(['breadcrumb.php','gateway-card gateway-flyer-detail','gateway-card__body','gateway-flyer-preview','gateway-flyer-detail__fields','gateway-button--danger','Back to Flyers']as$value)self::assertStringContainsString($value,$show);
        foreach(['breadcrumb.php','gateway-card gateway-property-form-card gateway-flyer-form','gateway-card__body','row gateway-form-grid','gateway-flyer-fixed-value','Replacement File','gateway-button--primary','Replace Flyer','gateway-button--secondary','>Cancel</a>']as$value)self::assertStringContainsString($value,$replace);
        foreach([$show,$replace]as$view)self::assertStringNotContainsString('style=',$view);
        foreach(['name="property"','name="month"','name="type"']as$editable)self::assertStringNotContainsString($editable,$replace);
    }

    public function testBreadcrumbsAreSharedFunctionalAndCurrentItemsAreNotLinks():void
    {
        foreach(['index','form','show','replace']as$name){$view=$this->view($name);self::assertStringContainsString("['label'=>'Corporate','url'=>'/corporate']",$view);self::assertStringContainsString("['label'=>'Marketing','url'=>'/corporate/marketing']",$view);self::assertStringContainsString("'current'=>true",$view);}
    }

    public function testUploadJavascriptBehaviorRemainsIntactAndAssetUsesCacheVersion():void
    {
        $js=(string)file_get_contents($this->root.'/public/assets/js/marketing-flyer-upload.js');
        foreach(['XMLHttpRequest','xhr.upload.onprogress','xhr.upload.onload','new FormData(form)','application/json','location.assign(data.redirect)']as$value)self::assertStringContainsString($value,$js);
        $footer=(string)file_get_contents($this->root.'/resources/views/components/footer.php');
        self::assertStringContainsString('marketing-flyer-upload.js?v=', $footer);
    }
}
