<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class CorporateInitializationWiringTest extends TestCase
{
    public function testCorporateContextRunsInsideAdministratorInitializationTransaction():void{$source=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/SystemInitializationService.php');$begin=strpos($source,'$this->transaction->begin()');$ensure=strpos($source,"corporateContext?->ensure('gateway-initialization')");$commit=strpos($source,'$this->transaction->commit()');self::assertNotFalse($begin);self::assertNotFalse($ensure);self::assertNotFalse($commit);self::assertLessThan($ensure,$begin);self::assertLessThan($commit,$ensure);}
    public function testNoDeleteOrCorporateMutationRouteExists():void{$routes=(string)file_get_contents(dirname(__DIR__,2).'/routes/web.php');self::assertStringNotContainsString("'DELETE'",$routes);self::assertStringNotContainsString('corporate-context/delete',$routes);}
}
