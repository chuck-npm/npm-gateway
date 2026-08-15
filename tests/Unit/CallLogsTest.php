<?php
declare(strict_types=1);
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\Exceptions\Domain\InvalidCallLogWorkbookException;
use NpmGateway\Services\{CallLogAccessPolicy,CallLogWorkbookParser};
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PHPUnit\Framework\TestCase;
final class CallLogsTest extends TestCase
{
 private array$files=[];
 protected function tearDown():void{foreach($this->files as$file)if(is_file($file))unlink($file);}
 public function testProtectedPrincipalIsTheAuthoritativeBoundary():void{$config=new ProtectedPrincipalConfig(str_repeat('A',26),str_repeat('E',26),['admin']);$policy=new CallLogAccessPolicy($config);self::assertTrue($policy->allows($this->user(str_repeat('A',26))));self::assertFalse($policy->allows($this->user(str_repeat('B',26))));self::assertFalse((new CallLogAccessPolicy(new ProtectedPrincipalConfig('','',['admin'])))->allows($this->user(str_repeat('A',26))));}
 public function testValidDetailedReportParsesExactHeadersTrailingBlanksAndMilliseconds():void{$file=$this->workbook([['+1 (229) 231-7979','2292317090','20260701 13:02:03.125','20260701 13:02:15.387',12.262],[null,null,null,null,null]]);$parsed=(new CallLogWorkbookParser(new PhoneFormatter()))->parse($this->upload($file));self::assertCount(1,$parsed['rows']);self::assertSame('+12292317979',$parsed['rows'][0]['calling_tn']);self::assertSame('+12292317090',$parsed['rows'][0]['called_tn']);self::assertSame('2026-07-01 13:02:03.125',$parsed['rows'][0]['started_at']);self::assertSame('2026-07-01 13:02:15.387',$parsed['rows'][0]['released_at']);self::assertSame('12.262',$parsed['rows'][0]['call_duration_seconds']);self::assertSame(hash_file('sha256',$file),$parsed['file_sha256']);}
 public function testWrongWorksheetHeadersMalformedRowsAndTimingAreRejected():void
 {
  foreach([
   fn()=> $this->workbook([], 'Other'),
   fn()=> $this->workbook([], 'Detailed Report',['Calling TN','Wrong','Start Time','Release Time','Call Duration']),
   fn()=> $this->workbook([['bad','+12292317090','07/01/2026 01:00:00 PM','07/01/2026 01:00:01 PM',1]]),
   fn()=> $this->workbook([['+12292317979','+12292317090','07/01/2026 01:00:02 PM','07/01/2026 01:00:01 PM',1]]),
   fn()=> $this->workbook([['+12292317979','+12292317090','07/01/2026 01:00:00 PM','07/01/2026 01:00:01 PM',99]]),
  ]as$make){try{(new CallLogWorkbookParser(new PhoneFormatter()))->parse($this->upload($make()));self::fail('Invalid workbook accepted.');}catch(InvalidCallLogWorkbookException){self::addToAssertionCount(1);}}
 }
 public function testSchemaRoutesViewsAndDocumentationExpressV1Contract():void
 {
  $root=dirname(__DIR__,2);$migration=(string)file_get_contents($root.'/database/migrations/202608140024_call_logs.php');foreach(['call_log_destinations','call_log_imports','call_logs','uq_call_log_destinations_called_tn','uq_call_log_imports_file_sha256','DATETIME(3)','DECIMAL(12,3) UNSIGNED','call_duration_seconds','idx_call_logs_started','+12297928075','Pine Hill','Highridge','Suburban','chk_call_log_destinations_identity']as$value)self::assertStringContainsString($value,$migration);
  $view=(string)file_get_contents($root.'/resources/views/admin/call-logs/index.php');$positions=[];foreach(['Property','Calling TN','Called TN','Start Time','Release Time','Duration (Seconds)']as$column){self::assertStringContainsString($column,$view);$positions[]=strpos($view,$column);}self::assertSame($positions,array_values($positions));foreach(['100','250','500','No call records have been imported.','Upload Call Log']as$value)self::assertStringContainsString($value,$view);
  $routes=(string)file_get_contents($root.'/routes/web.php');self::assertStringContainsString("'/admin/call-logs'",$routes);self::assertStringContainsString("'protected-principal'",$routes);self::assertFileExists($root.'/docs/call-logs.md');
 }
 private function user(string$public):AuthenticatedUser{return new AuthenticatedUser(1,2,$public,str_repeat('E',26),'user','User');}
 private function upload(string$path):array{return['name'=>'Lumen.xls','tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];}
 private function workbook(array$rows,string$sheetName='Detailed Report',?array$headers=null):string{$book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle($sheetName);$sheet->fromArray($headers??CallLogWorkbookParser::HEADERS,null,'A1');$row=2;foreach($rows as$values)$sheet->fromArray($values,null,'A'.$row++);$file=tempnam(sys_get_temp_dir(),'call-log-');$this->files[]=$file;(new Xls($book))->save($file);$book->disconnectWorksheets();return$file;}
}
