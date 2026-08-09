<?php declare(strict_types=1);namespace Tests\Feature;
use NpmGateway\Configuration\AuthenticationConfig;use NpmGateway\Container\ServiceProvider;use NpmGateway\Contracts\ClockInterface;use NpmGateway\Contracts\CorporateToolsProviderInterface;use NpmGateway\Contracts\GatewayPdfRendererInterface;use NpmGateway\Http\Controllers\OperationsOverviewController;use NpmGateway\Http\Controllers\OperationsRmAuditPdfController;use NpmGateway\Security\CsrfService;use NpmGateway\Services\CorporateAccessService;use NpmGateway\Services\OperationsRmAuditOverviewService;use NpmGateway\Services\OperationsRmCorrectionOverviewService;use PHPUnit\Framework\TestCase;
final class ApplicationCompositionRootTest extends TestCase
{
 public function testOperationsOverviewCompositionUsesTypedServicesInConstructorOrder():void
 {
  $application=require dirname(__DIR__,2).'/bootstrap/app.php';$container=ServiceProvider::build($application);$session=[];$controller=new OperationsOverviewController($access=$container->get(CorporateAccessService::class),$corrections=$container->get(OperationsRmCorrectionOverviewService::class),$audits=$container->get(OperationsRmAuditOverviewService::class),$clock=$container->get(ClockInterface::class),$tools=$container->get(CorporateToolsProviderInterface::class),new CsrfService($session),$application['root'].'/resources/views');
  self::assertInstanceOf(OperationsOverviewController::class,$controller);self::assertInstanceOf(CorporateAccessService::class,$access);self::assertInstanceOf(OperationsRmCorrectionOverviewService::class,$corrections);self::assertInstanceOf(OperationsRmAuditOverviewService::class,$audits);self::assertInstanceOf(ClockInterface::class,$clock);self::assertInstanceOf(CorporateToolsProviderInterface::class,$tools);self::assertInstanceOf(AuthenticationConfig::class,$container->get(AuthenticationConfig::class));
  self::assertInstanceOf(OperationsRmAuditPdfController::class,new OperationsRmAuditPdfController($access,$audits,$container->get(GatewayPdfRendererInterface::class),$clock,$application['root'].'/resources/views'));
  $container->get(\mysqli::class)->close();
 }
 public function testPublicCompositionRootContainsTheSameTypedSequence():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/public/index.php');$expected='$container->get(CorporateAccessService::class),$container->get(OperationsRmCorrectionOverviewService::class),$container->get(OperationsRmAuditOverviewService::class),$container->get(ClockInterface::class),$container->get(CorporateToolsProviderInterface::class),$csrf,$views';self::assertStringContainsString('new OperationsOverviewController('.$expected.')',$source);
 }
}
