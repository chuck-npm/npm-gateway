<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\ApartmentsComSchema;
use NpmGateway\Exceptions\Domain\InvalidApartmentsWorkbookException;
use NpmGateway\Services\ApartmentsWorkbookParser;
use NpmGateway\Services\{ApartmentsService,CallLogAccessPolicy};
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\{AuthenticatedRequestContext,Request};
use NpmGateway\Http\Controllers\ApartmentsController;
use NpmGateway\Security\CsrfService;
use NpmGateway\Support\FlashSession;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PhpOffice\PhpSpreadsheet\{Spreadsheet,Worksheet\Worksheet,Writer\Xlsx};
use PHPUnit\Framework\TestCase;
final class ApartmentsComTest extends TestCase
{
 private array$files=[];
 protected function tearDown():void{foreach($this->files as$file)if(is_file($file))unlink($file);}
 public function testCertifiedMappingsExcludePineManorAndSuburban():void{self::assertSame(['Boulder Trails'=>'BT','Crumley Farms'=>'CF','Flamingo Flats'=>'FF','Highridge'=>'HR','Maplewind MHC'=>'MW','Pearce Point'=>'PP','Pine Hill'=>'PH','Sizemore'=>'SM','Wunderpark'=>'WP'],ApartmentsComSchema::MAPPINGS);self::assertArrayNotHasKey('Pine Manor',ApartmentsComSchema::MAPPINGS);self::assertArrayNotHasKey('Suburban',ApartmentsComSchema::MAPPINGS);}
 public function testParserDetectsStructuralSheetsParsesRowsAndPreservesSourceQuality():void{$file=$this->book('Phone Calls - 2','Emails - 2',[$this->call('00:08','Missed'),$this->call('01:12','Completed')],[$this->email('(229) 555-0101'),$this->email('238')]);$parsed=$this->parser()->parse($this->upload($file));self::assertCount(2,$parsed['calls']);self::assertCount(2,$parsed['email_leads']);self::assertSame([8,72],array_column($parsed['calls'],'call_duration_seconds'));self::assertSame(['Missed','Completed'],array_column($parsed['calls'],'status'));self::assertSame('Listen',$parsed['calls'][0]['call_recording_value']);self::assertSame('(229) 555-0101',$parsed['email_leads'][0]['renter_phone_raw']);self::assertSame('+12295550101',$parsed['email_leads'][0]['renter_phone_normalized']);self::assertSame('238',$parsed['email_leads'][1]['renter_phone_raw']);self::assertNull($parsed['email_leads'][1]['renter_phone_normalized']);self::assertSame('renter@example.test',$parsed['email_leads'][1]['renter_email']);self::assertSame('2026-08-01 01:02:03',$parsed['source_started_at']);}
 public function testChangingCountSuffixAndTrailingBlankRowsWork():void{$file=$this->book('Phone Calls - 1','Emails - 1',[$this->call('60:00','Completed')],[$this->email('')],true);$parsed=$this->parser()->parse($this->upload($file));self::assertSame(3600,$parsed['calls'][0]['call_duration_seconds']);self::assertCount(1,$parsed['email_leads']);}
 public function testMissingDuplicateHeadersAndSuffixMismatchFailClosed():void
 {
  $cases=[
   $this->book('Other - 1','Emails - 1',[$this->call()],[$this->email()]),
   $this->book('Phone Calls - 1','Other - 1',[$this->call()],[$this->email()]),
   $this->book('Phone Calls - 2','Emails - 1',[$this->call()],[$this->email()]),
   $this->book('Phone Calls - 1','Emails - 1',[$this->call()],[$this->email()],false,true),
  ];foreach($cases as$file){try{$this->parser()->parse($this->upload($file));self::fail('Invalid workbook accepted.');}catch(InvalidApartmentsWorkbookException){self::addToAssertionCount(1);}}
  $duplicate=$this->book('Phone Calls - 1','Emails - 1',[$this->call()],[$this->email()]);$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($duplicate);$copy=clone$book->getSheet(0);$copy->setTitle('Phone Calls - 9');$book->addSheet($copy);(new Xlsx($book))->save($duplicate);$book->disconnectWorksheets();$this->expectException(InvalidApartmentsWorkbookException::class);$this->parser()->parse($this->upload($duplicate));
 }
 public function testRoutesSchemaAndAuthorizationContract():void{$root=dirname(__DIR__,2);$routes=require$root.'/routes/web.php';foreach(['/admin/apartments','/admin/apartments/upload']as$route)self::assertSame(['authentication','protected-principal'],$routes[$route]['middleware']);$migration=(string)file_get_contents($root.'/database/migrations/202608150026_apartments_com_imports.php');foreach(ApartmentsComSchema::TABLES as$table)self::assertStringContainsString('CREATE TABLE '.$table,$migration);foreach(['file_sha256','property_id,occurred_at','call_duration_seconds INT UNSIGNED','ON DELETE RESTRICT']as$text)self::assertStringContainsString($text,$migration);}
 public function testControllerUsesProtectedPrincipalForUploadAndDirectPost():void{$protected=str_repeat('A',26);$policy=new CallLogAccessPolicy(new ProtectedPrincipalConfig($protected,str_repeat('E',26),['admin']));$native=[];$flash=[];$tools=new class implements CorporateToolsProviderInterface{public function tools(AuthenticatedRequestContext$context):array{return[];}};$service=(new ReflectionClass(ApartmentsService::class))->newInstanceWithoutConstructor();$controller=new ApartmentsController($policy,$service,$tools,new CsrfService($native),new FlashSession($flash),dirname(__DIR__,2).'/resources/views');$context=fn(string$id)=>new AuthenticatedRequestContext(new AuthenticatedUser(1,2,$id,str_repeat('F',26),'user','User'),'token');self::assertSame(200,$controller->upload($context($protected))->status);self::assertSame(403,$controller->upload($context(str_repeat('B',26)))->status);self::assertSame(403,$controller->store(new Request('POST','/admin/apartments/upload'),$context(str_repeat('B',26)))->status);}
 private function parser():ApartmentsWorkbookParser{return new ApartmentsWorkbookParser(new PhoneFormatter());}
 private function upload(string$file):array{return['name'=>'apartments.xlsx','tmp_name'=>$file,'size'=>filesize($file),'error'=>UPLOAD_ERR_OK];}
 private function call(string$duration='00:30',string$status='Completed'):array{return['Maplewind MHC','08/01/2026 01:02:03 AM','(229) 555-0101','(229) 555-0102','(229) 555-0103',$duration,'Listen',$status];}
 private function email(string$phone='(229) 555-0101'):array{return['Pearce Point','08/01/2026 02:03:04 AM','Renter Name',$phone,'renter@example.test'];}
 private function book(string$callTitle,string$emailTitle,array$calls,array$emails,bool$trailing=false,bool$badHeader=false):string{$book=new Spreadsheet();$callsSheet=$book->getActiveSheet();$callsSheet->setTitle($callTitle);$headers=ApartmentsWorkbookParser::CALL_HEADERS;if($badHeader)$headers[2]='Wrong Header';$callsSheet->fromArray($headers,null,'A1');$callsSheet->fromArray($calls,null,'A2');$emailsSheet=new Worksheet($book,$emailTitle);$book->addSheet($emailsSheet);$emailsSheet->fromArray(ApartmentsWorkbookParser::EMAIL_HEADERS,null,'A1');$emailsSheet->fromArray($emails,null,'A2');if($trailing){$callsSheet->setCellValue('H8','');$emailsSheet->setCellValue('E8','');}$file=tempnam(sys_get_temp_dir(),'apartments-');$this->files[]=$file;(new Xlsx($book))->save($file);$book->disconnectWorksheets();return$file;}
}
