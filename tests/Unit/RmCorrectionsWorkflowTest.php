<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use NpmGateway\Exceptions\Domain\InvalidRmCorrectionException;
use NpmGateway\Notifications\GatewayEmailRenderer;
use NpmGateway\Notifications\RmCorrectionEmailSender;
use NpmGateway\Services\RmCorrectionStatus;
use NpmGateway\Services\RmCorrectionValidator;
use PHPUnit\Framework\TestCase;
final class RmCorrectionsWorkflowTest extends TestCase
{
 public function testStatusAndValidationContract():void
 {
  self::assertSame(['pending_review','approved','denied','more_information_needed'],array_keys(RmCorrectionStatus::LABELS));$v=new RmCorrectionValidator();$data=$v->submission(['lot_address'=>' Lot 12 ','tenant_name'=>"O'Neil, Pat",'correction_request'=>"Remove the automatic late fee.\r\nPayment was timely.",'property_id'=>'999','submitted_by_user_id'=>'999']);self::assertSame('Lot 12',$data['lot_address']);self::assertArrayNotHasKey('property_id',$data);self::assertStringContainsString("\n",$data['correction_request']);
  foreach([['decision'=>'approved','comments'=>''],['decision'=>'invalid','comments'=>'Reason']] as $post){try{$v->decision($post);self::fail('Invalid decision accepted.');}catch(InvalidRmCorrectionException){self::addToAssertionCount(1);}}
 }
 public function testIndependentRoutesCategoryAndImmutableSchema():void
 {
  $root=dirname(__DIR__,2);$routes=require$root.'/routes/web.php';foreach(['/corporate/rm-corrections','/corporate/rm-corrections/{requestPublicId}','/corporate/rm-corrections/{requestPublicId}/decision']as$p){self::assertArrayHasKey($p,$routes);self::assertContains('rm-corrections-access',$routes[$p]['middleware']);}$config=require$root.'/config/corporate-access.php';self::assertSame('RM Corrections',$config['categories']['rm-corrections']);$migration=(string)file_get_contents($root.'/database/migrations/202608060018_rm_corrections.php');foreach(['ON DELETE RESTRICT','more_information_needed','manager_responded','Cannot roll back RM Corrections']as$s)self::assertStringContainsString($s,$migration);self::assertStringNotContainsString('ON DELETE CASCADE',$migration);
 }
 public function testEmailIsEscapedPlainTextEquivalentAndTestRecipientWins():void
 {
  $sent=[];$sender=new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'noc@npmparks.com','reviewer_email'=>'kiyana@npmparks.com','app_url'=>'https://gateway.test'],function(...$args)use(&$sent){$sent=$args;return true;},new GatewayEmailRenderer());$r=['public_id'=>str_repeat('A',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','lot_address'=>'12 <Main>','tenant_name'=>'Pat & Lee','submitted_by_name'=>'Manager','submitted_at'=>'2026-08-06 10:00:00','correction_request'=>'Remove <script>alert(1)</script> fee','manager_email'=>'manager@example.com'];self::assertTrue($sender->submission($r));self::assertSame('noc@npmparks.com',$sent[0]);self::assertStringNotContainsString('kiyana@npmparks.com',implode(' ',array_map('strval',$sent)));self::assertStringContainsString('&lt;script&gt;',$sent[2]);self::assertStringContainsString('Correction Request:',$sent[3]);self::assertStringContainsString('/corporate/rm-corrections/',$sent[3]);
 }
 public function testEveryMessageUsesTestRecipientAndProductionRecipientsReceiveNothing():void
 {
  $recipients=[];$sender=new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'test@example.com','reviewer_email'=>'reviewer@example.com','app_url'=>'https://gateway.test'],function(string $to)use(&$recipients){$recipients[]=$to;return true;},new GatewayEmailRenderer());$r=['public_id'=>str_repeat('A',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','lot_address'=>'99A','tenant_name'=>'Tenant','submitted_by_name'=>'Amanda Watson','submitted_at'=>'2026-08-07 10:00:00','correction_request'=>'Remove the assessed late fee.','manager_email'=>'manager@example.com'];self::assertTrue($sender->submission($r));self::assertTrue($sender->response($r,'Payment receipt attached.'));foreach(['approved','denied','more_information_needed']as$status){$r['status']=$status;self::assertTrue($sender->decision($r,'Workflow comment.'));}self::assertSame(array_fill(0,5,'test@example.com'),$recipients);self::assertNotContains('reviewer@example.com',$recipients);self::assertNotContains('manager@example.com',$recipients);
 }
 public function testRecipientConfigurationFailsClosedAndProductionRoutingIsDirectional():void
 {
  $r=['public_id'=>str_repeat('A',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','lot_address'=>'99A','tenant_name'=>'Tenant','submitted_by_name'=>'Amanda Watson','submitted_at'=>'2026-08-07 10:00:00','correction_request'=>'Remove the assessed late fee.','manager_email'=>'manager@example.com','status'=>'approved'];self::assertFalse((new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'','reviewer_email'=>'reviewer@example.com','app_url'=>'https://gateway.test'],static fn()=>true))->submission($r));self::assertFalse((new RmCorrectionEmailSender(['test_mode'=>false,'reviewer_email'=>'','app_url'=>'https://gateway.test'],static fn()=>true))->submission($r));$to=[];$sender=new RmCorrectionEmailSender(['test_mode'=>false,'reviewer_email'=>'reviewer@example.com','app_url'=>'https://gateway.test'],function(string $recipient)use(&$to){$to[]=$recipient;return true;});self::assertTrue($sender->submission($r));self::assertTrue($sender->decision($r,'Approved.'));self::assertSame(['reviewer@example.com','manager@example.com'],$to);
 }
 public function testEnvironmentLookupMatchesImmutableDotenvAndApprovedBooleans():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/config/rm-corrections.php');foreach(['$_ENV[$key]','$_SERVER[$key]','getenv($key)','FILTER_VALIDATE_BOOL']as$required)self::assertStringContainsString($required,$source);foreach(['true','1','yes','on']as$value)self::assertTrue(filter_var($value,FILTER_VALIDATE_BOOL));foreach(['false','0','no','off','']as$value)self::assertFalse(filter_var($value,FILTER_VALIDATE_BOOL));
 }
 public function testRenderingAndTransportFailuresAreSafeAndDiagnosticsAreSanitized():void
 {
  $r=['public_id'=>str_repeat('A',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','lot_address'=>'99A','tenant_name'=>'Tenant','submitted_by_name'=>'Amanda Watson','submitted_at'=>'2026-08-07 10:00:00','correction_request'=>'Remove the assessed late fee.','manager_email'=>'manager@example.com'];$throwingRenderer=new class extends GatewayEmailRenderer{public function render(\NpmGateway\ValueObjects\GatewayEmailMessage $message):array{throw new \RuntimeException('SMTP_PASSWORD=secret');}};self::assertFalse((new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'test@example.com'],$delivery=null,$throwingRenderer))->submission($r));self::assertFalse((new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'test@example.com'],static function(){throw new \RuntimeException('SMTP_PASSWORD=secret');}))->submission($r));$source=(string)file_get_contents(dirname(__DIR__,2).'/app/Notifications/RmCorrectionEmailSender.php');foreach(['workflow','rm_corrections','message_type','failure_code','exception_class','recipient_mode']as$allowed)self::assertStringContainsString($allowed,$source);foreach(['getMessage()','Password','Username','content[\'html\']','content[\'text\']']as$forbidden)self::assertStringNotContainsString($forbidden,substr($source,strpos($source,'private function failure')));
 }
 public function testSubmissionNavigationAndIndependentPersistenceMessages():void
 {
  $root=dirname(__DIR__,2);$controller=(string)file_get_contents($root.'/app/Http/Controllers/RmCorrectionController.php');$view=(string)file_get_contents($root.'/resources/views/community-actions/rm-corrections/show.php');foreach(['RM correction submitted successfully.','The request was saved, but the reviewer notification could not be delivered.',"put('rm_success'","put('rm_warning'",'/rm-corrections/\'.$result[\'public_id\']']as$required)self::assertStringContainsString($required,$controller);self::assertStringContainsString('Back to RM Corrections',$view);self::assertStringContainsString('$e($context->propertySlug)',$view);self::assertStringContainsString('gateway-alert--success',$view);self::assertStringContainsString('gateway-alert--warning',$view);self::assertStringNotContainsString('property_id',$view);self::assertStringNotContainsString('request[\'id\']',$view);
 }
 public function testAuditSourcesExcludeNarrativesAndRawPost():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/RmCorrectionService.php');foreach(['rm_correction.submitted','rm_correction.decision_recorded','rm_correction.manager_responded']as$s)self::assertStringContainsString($s,$source);foreach(["'tenant_name'=>\$","'correction_request'=>\$",'raw_post','session_data','email_address']as$s)self::assertStringNotContainsString($s,$source);
 }
}
