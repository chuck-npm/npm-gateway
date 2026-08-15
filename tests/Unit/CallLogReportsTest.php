<?php
declare(strict_types=1);
use NpmGateway\Repositories\CallLogReportRepository;
use NpmGateway\Services\{CallLogAccessPolicy,CallLogReportDateRangeFactory,CallLogReportService};
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\{AuthenticatedRequestContext,Request};
use NpmGateway\Http\Controllers\CallLogReportController;
use NpmGateway\Security\CsrfService;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;
final class CallLogReportsTest extends TestCase
{
 public function testDatesAreRequiredStrictInclusiveAndOrdered():void
 {
  $factory=new CallLogReportDateRangeFactory();$blank=$factory->fromQuery([]);self::assertFalse($blank->requested);self::assertSame([],$blank->errors);
  self::assertSame(['to'=>'To Date is required.'],$factory->fromQuery(['from'=>'2026-01-01'])->errors);
  self::assertSame(['from'=>'Enter a valid From Date.'],$factory->fromQuery(['from'=>'2026-02-30','to'=>'2026-03-01'])->errors);
  self::assertSame(['date_range'=>'From Date must be on or before To Date.'],$factory->fromQuery(['from'=>'2026-01-08','to'=>'2026-01-01'])->errors);
  $valid=$factory->fromQuery(['from'=>'2026-01-01','to'=>'2026-01-07']);self::assertTrue($valid->valid());self::assertSame('2026-01-08',$valid->toExclusive);
 }
 public function testOwnerRosterAndReportContractAreExplicit():void
 {
  self::assertSame(['BT','CF','FF','HR','MW','PP','PH','PM','SM','WP'],CallLogReportRepository::OWNER_PROPERTY_CODES);$root=dirname(__DIR__,2);$repo=(string)file_get_contents($root.'/app/Repositories/CallLogReportRepository.php');foreach(['COUNT(*) total_calls','call_duration_seconds < 35.000','call_duration_seconds >= 35.000','started_at>=?','started_at<?','GROUP BY destination_id','LEFT JOIN','p.status=\'active\'']as$text)self::assertStringContainsString($text,$repo);self::assertStringNotContainsString('Suburban',$repo);
  $routes=require$root.'/routes/web.php';foreach(['/admin/call-log-reports','/admin/call-log-reports/facebook-performance']as$route){self::assertSame(['authentication','protected-principal'],$routes[$route]['middleware']);self::assertSame(['GET'],$routes[$route]['methods']);}
  $view=(string)file_get_contents($root.'/resources/views/admin/call-log-reports/facebook-performance.php');foreach(['Total Calls','No Answer','Answered','Percent Answered','Company Totals','Reporting Period:','method="get"','required']as$text)self::assertStringContainsString($text,$view);self::assertStringNotContainsString('pagination',$view);
 }
 public function testAdministrationKeepsLogsAndReportsDistinct():void{$admin=(string)file_get_contents(dirname(__DIR__,2).'/app/Http/Controllers/AdminController.php');foreach(["new ToolCard('call-logs'","new ToolCard('call-log-reports'",'/admin/call-logs','/admin/call-log-reports']as$text)self::assertStringContainsString($text,$admin);}
 public function testControllerEnforcesProtectedPrincipalOnLandingAndDirectReportUrl():void
 {
  $protected=str_repeat('A',26);$policy=new CallLogAccessPolicy(new ProtectedPrincipalConfig($protected,str_repeat('E',26),['admin']));$session=[];$tools=new class implements CorporateToolsProviderInterface{public function tools(AuthenticatedRequestContext$context):array{return[];}};$service=(new ReflectionClass(CallLogReportService::class))->newInstanceWithoutConstructor();$controller=new CallLogReportController($policy,$service,$tools,new CsrfService($session),dirname(__DIR__,2).'/resources/views');
  $context=fn(string$id)=>new AuthenticatedRequestContext(new AuthenticatedUser(1,2,$id,str_repeat('F',26),'user','User'),'token');self::assertSame(200,$controller->index($context($protected))->status);self::assertSame(403,$controller->index($context(str_repeat('B',26)))->status);self::assertSame(403,$controller->facebookPerformance(new Request('GET','/admin/call-log-reports/facebook-performance'),$context(str_repeat('B',26)))->status);
 }
}
