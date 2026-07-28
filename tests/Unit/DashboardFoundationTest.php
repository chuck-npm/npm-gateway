<?php
declare(strict_types=1);
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Services\UniversalToolProvider;
use NpmGateway\Support\Navigation;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class DashboardFoundationTest extends TestCase
{
 #[DataProvider('countCases')]
 public function testSummaryIsTypedImmutableAndDetectsState(int $properties,bool $initial):void
 {
  $summary=$this->service($properties)->forUser($this->user());
  self::assertSame($properties,$summary->propertyCount);self::assertSame(1,$summary->employeeCount);self::assertSame(1,$summary->userCount);self::assertSame(1,$summary->activeUserCount);self::assertSame(0,$summary->activeAssignmentCount);self::assertSame($initial,$summary->initialSetup);self::assertSame('Test Administrator',$summary->displayName);self::assertSame('Sysadmin',$summary->jobTitle);
  self::assertTrue((new ReflectionClass($summary))->isReadOnly());
 }
 public static function countCases():iterable{yield 'initial'=>[0,true];yield 'configured'=>[1,false];}
 public function testAuthenticatedLayoutIsAccessibleAndContainsOnlyRealDestinations():void
 {
  $html=$this->render(0);
  foreach(['href="#main-content"','id="main-content"','aria-label="Primary navigation"','aria-current="page"','data-bs-toggle="collapse"','NPM Gateway','Test Administrator','Sysadmin','method="post" action="/logout"'] as $expected)self::assertStringContainsString($expected,$html);
  foreach(['href="/properties"','href="/employees"','href="/users"','sidebar','Lorem Ipsum','Recent Activity','chart','TEST-session-secret','href="#"'] as $forbidden)self::assertStringNotContainsString($forbidden,$html);
 }
 public function testInitialAndNormalStatesRemainTruthful():void
 {
  $initial=$this->render(0);self::assertStringContainsString('Gateway Setup',$initial);self::assertStringContainsString('Add the first property when Property Management becomes available.',$initial);self::assertStringNotContainsString('Add Property</a>',$initial);
  $normal=$this->render(1);self::assertStringContainsString('Gateway foundation ready',$normal);self::assertStringNotContainsString('Add the first property',$normal);
 }
 public function testNavigationConfigurationUsesOnlyExistingDashboardRoute():void
 {
  $items=Navigation::forRoute('/dashboard',dirname(__DIR__,2));self::assertCount(1,$items);self::assertSame('/dashboard',$items[0]['url']);self::assertTrue($items[0]['active']);
 }
 public function testArchitectureBoundaries():void
 {
  $root=dirname(__DIR__,2);$controller=file_get_contents($root.'/app/Http/Controllers/DashboardController.php');self::assertDoesNotMatchRegularExpression('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b/i',$controller);self::assertStringNotContainsString('Repositories\\',$controller);
  $service=file_get_contents($root.'/app/Services/DashboardSummaryService.php');self::assertDoesNotMatchRegularExpression('/\\b(?:INSERT|UPDATE|DELETE|begin_transaction|commit|rollback)\\b/i',$service);
  foreach(glob($root.'/resources/views/*.php')?:[] as $view){$source=file_get_contents($view);self::assertDoesNotMatchRegularExpression('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b/i',$source);self::assertStringNotContainsString('$_ENV',$source);}
  self::assertFileDoesNotExist($root.'/database/migrations/202607270003_dashboard.php');
 }
 private function render(int $properties):string
 {
  $state=[];$controller=new DashboardController(new CsrfService($state),new DashboardHomeService($this->service($properties),new UniversalToolProvider()),dirname(__DIR__,2).'/resources/views');
  return $controller->index(new AuthenticatedRequestContext($this->user(),'TEST-session-secret'))->body;
 }
 private function service(int $properties):DashboardSummaryService
 {
  $store=new class($properties) implements DashboardSummaryStoreInterface{
   public function __construct(private readonly int $properties){}
   public function counts():array{return ['property_count'=>$this->properties,'employee_count'=>1,'user_count'=>1,'active_user_count'=>1,'active_assignment_count'=>0];}
  };
  return new DashboardSummaryService($store);
 }
 private function user():AuthenticatedUser{return new AuthenticatedUser(17,18,str_repeat('U',26),str_repeat('E',26),'testadmin','Test Administrator','Sysadmin','corporate');}
}
