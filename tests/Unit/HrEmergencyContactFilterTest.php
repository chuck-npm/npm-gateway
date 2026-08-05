<?php
declare(strict_types=1);
use NpmGateway\Http\Controllers\HrEmergencyContactController;
use NpmGateway\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class HrEmergencyContactFilterTest extends TestCase
{
 public static function queries():array{return [
  'no query parameters'=>[[],['status'=>'all','employeeClass'=>'all','search'=>'']],
  'only search'=>[['search'=>'  Avery  '],['status'=>'all','employeeClass'=>'all','search'=>'Avery']],
  'only status'=>[['status'=>'missing'],['status'=>'missing','employeeClass'=>'all','search'=>'']],
  'only employee class'=>[['employee_class'=>'manager'],['status'=>'all','employeeClass'=>'manager','search'=>'']],
  'combined filters'=>[['status'=>'completed','employee_class'=>'maintenance','search'=>'Lee'],['status'=>'completed','employeeClass'=>'maintenance','search'=>'Lee']],
  'invalid status'=>[['status'=>'unknown'],['status'=>'all','employeeClass'=>'all','search'=>'']],
  'invalid employee class'=>[['employee_class'=>'administrator'],['status'=>'all','employeeClass'=>'all','search'=>'']],
 ];}
 #[DataProvider('queries')]
 public function testOptionalFiltersAreSafelyNormalized(array $query,array $expected):void{$method=new ReflectionMethod(HrEmergencyContactController::class,'filters');$actual=$method->invoke(null,new Request('GET','/corporate/human-resources/emergency-contacts',[],[],[],$query));self::assertSame($expected,$actual);}
 public function testUnfilteredPresentationContainsAllOptionsWithoutWarnings():void{$root=dirname(__DIR__,2);$rows=[];$summary=['completed'=>0,'missing'=>0];$status='all';$employeeClass='all';$search='';$success='';$csrfToken='csrf';$logoutCsrfToken='csrf';$navbarCorporateItems=[];$user=new \NpmGateway\ValueObjects\AuthenticatedUser(1,1,str_repeat('U',26),str_repeat('E',26),'tester','Test User');set_error_handler(static function(int $severity,string $message):never{throw new ErrorException($message,0,$severity);});try{ob_start();require $root.'/resources/views/human-resources/emergency-contacts/index.php';$html=(string)ob_get_clean();}finally{restore_error_handler();}self::assertStringContainsString('All statuses',$html);self::assertStringContainsString('All classes',$html);self::assertStringContainsString('value="all" selected',$html);self::assertStringContainsString('0 Completed',$html);self::assertStringContainsString('0 Missing',$html);}
}
