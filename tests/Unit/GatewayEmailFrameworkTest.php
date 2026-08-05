<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use NpmGateway\Notifications\ApplicationReviewEmailSender;
use NpmGateway\Notifications\GatewayEmailRenderer;
use NpmGateway\ValueObjects\GatewayEmailMessage;
use PHPUnit\Framework\TestCase;
final class GatewayEmailFrameworkTest extends TestCase
{
 public function testSharedRendererProducesBrandedMultipartEmailSafeOutput():void
 {
  $rendered=(new GatewayEmailRenderer())->render(new GatewayEmailMessage('Safe workflow preview.','WORKFLOW','Test <Title>','Alpha & Community','Pending Review','pending', [['label'=>'Prospect','value'=>'Person <script>'],['label'=>'Property','value'=>'Alpha & Community']],[['title'=>'Comments','body'=>"First <b>line</b>\nSecond line"]],'Open Workflow','https://gateway.example.test/workflow/'.str_repeat('R',26),'Internal use only.'));
  foreach(['<!doctype html>','NPM Gateway','Internal Workflow Notification','WORKFLOW','Test &lt;Title&gt;','Alpha &amp; Community','Pending Review','Prospect','Person &lt;script&gt;','Comments','First &lt;b&gt;line&lt;/b&gt;','white-space:pre-line','role="presentation"','max-width:640px','@media only screen and (max-width:600px)','v:roundrect','Open Workflow','If the button does not work','https://gateway.example.test/workflow/','This message was generated automatically by NPM Gateway.','Please do not reply','NPM Properties Inc.',(new \DateTimeImmutable())->format('Y')] as $required)self::assertStringContainsString($required,$rendered['html']);
  self::assertStringContainsString('padding:28px 32px 24px',$rendered['html']);self::assertStringContainsString('border-top:2px solid #c5ced9',$rendered['html']);self::assertStringContainsString('font-size:15px;line-height:1.4;font-weight:700;',$rendered['html']);
  foreach(['<script>','<b>line</b>','<link','javascript:','tracking','<img','@font-face','fonts.googleapis.com'] as $forbidden)self::assertStringNotContainsString($forbidden,$rendered['html']);
  foreach(['NPM GATEWAY','Internal Workflow Notification','Test <Title>','Alpha & Community','Status: Pending Review','Prospect: Person <script>','Comments:',"First <b>line</b>\nSecond line",'Open Workflow:','https://gateway.example.test/workflow/','Please do not reply.'] as $required)self::assertStringContainsString($required,$rendered['text']);
 }
 public function testOptionalEmptySectionsAndFieldsDoNotRenderBlankStructure():void
 {
  $rendered=(new GatewayEmailRenderer())->render(new GatewayEmailMessage('Preview','WORKFLOW','Minimal',null,null,'neutral',[],[['title'=>'Blank','body'=>'  ']]));self::assertStringNotContainsString('Blank',$rendered['html']);self::assertStringNotContainsString('If the button does not work',$rendered['html']);self::assertSame('',$rendered['url']);
 }
 public function testTitleStatusSubtitleOrderAndControlledRowEmphasis():void
 {
  $renderer=new GatewayEmailRenderer();$rendered=$renderer->render(new GatewayEmailMessage('Preview','WORKFLOW','Ordered Title','Ordered Subtitle','Pending Review','pending',[['label'=>'Property','value'=>'Ordinary Property'],['label'=>'Reusable Context','value'=>'Emphasized Value','emphasized'=>true]]));$html=$rendered['html'];$title=strpos($html,'Ordered Title</h1>');$status=strpos($html,'Pending Review</span>');$subtitle=strpos($html,'Ordered Subtitle</p>');self::assertIsInt($title);self::assertIsInt($status);self::assertIsInt($subtitle);self::assertLessThan($status,$title);self::assertLessThan($subtitle,$status);self::assertStringContainsString('font-weight:400;word-break:break-word;">Ordinary Property',$html);self::assertStringContainsString('font-weight:700;word-break:break-word;">Emphasized Value',$html);
  $without=$renderer->render(new GatewayEmailMessage('Preview','WORKFLOW','No Status','Subtitle Only'));self::assertLessThan(strpos($without['html'],'Subtitle Only</p>'),strpos($without['html'],'No Status</h1>'));self::assertStringNotContainsString('border-radius:999px',$without['html']);
  $senderSource=(string)file_get_contents(dirname(__DIR__,2).'/app/Notifications/ApplicationReviewEmailSender.php');self::assertSame(2,substr_count($senderSource,"'emphasized'=>true"));
  $this->expectException(\InvalidArgumentException::class);new GatewayEmailMessage('Preview','WORKFLOW','Unsafe',null,null,'neutral',[['label'=>'Property','value'=>'Value','emphasized'=>true,'style'=>'color:red']]);
 }
 public function testApplicationReviewSubmissionUsesSharedFrameworkAndNewCorporateRoute():void
 {
  $review=$this->review('pending_review');$content=(new ApplicationReviewEmailSender(['app_url'=>'https://gateway.example.test']))->submissionContent($review);
  foreach(['NPM Gateway','Internal Workflow Notification','APPLICATION REVIEWS','Application Review Submitted','Alpha Community','Pending Review','Prospect Person','Manager User','August 5, 2026 at 1:34 AM','Rent Manager Documents','Confirmed','Comments for Reviewer','Manager &lt;comments&gt;','Review Application','https://gateway.example.test/corporate/application-reviews/'.$review['public_id']] as $required)self::assertStringContainsString($required,$content['html']);
  foreach(['/corporate/operations/application-reviews','Manager <comments>'] as $forbidden)self::assertStringNotContainsString($forbidden,$content['html']);
  self::assertLessThan(strpos($content['html'],'Pending Review</span>'),strpos($content['html'],'Application Review Submitted</h1>'));self::assertLessThan(strpos($content['html'],'Alpha Community</p>'),strpos($content['html'],'Pending Review</span>'));self::assertStringContainsString('font-weight:700;word-break:break-word;">Alpha Community',$content['html']);self::assertStringContainsString('font-weight:400;word-break:break-word;">Prospect Person',$content['html']);
  foreach(['Application Review Submitted','Status: Pending Review','Rent Manager Documents: Confirmed','Comments for Reviewer:','Manager <comments>','Review Application:'] as $required)self::assertStringContainsString($required,$content['text']);
  $review['manager_comments']='';$without=(new ApplicationReviewEmailSender(['app_url'=>'https://gateway.example.test']))->submissionContent($review);self::assertStringNotContainsString('Comments for Reviewer',$without['html']);
 }
 public function testApprovedAndDeniedDecisionEmailsUseSharedStatusAndManagerRoute():void
 {
  foreach(['approved'=>['Approved','background:#d1e7dd'],'denied'=>['Denied','background:#f8d7da']] as $status=>[$label,$tone]){$review=$this->review($status);$content=(new ApplicationReviewEmailSender(['app_url'=>'https://gateway.example.test']))->decisionContent($review);foreach(['Application Review '.$label,$tone,'Decision',$label,'Reviewed By','Reviewer User','Reviewed At','Reviewer Comments','Reviewer &lt;basis&gt;','View Application Review','https://gateway.example.test/community-actions/alpha/application-reviews/'.$review['public_id'],'font-weight:700;word-break:break-word;'] as $required)self::assertStringContainsString($required,$content['html']);foreach(['Application Review '.$label,'Decision: '.$label,'Reviewer Comments:', 'Reviewer <basis>','View Application Review:'] as $required)self::assertStringContainsString($required,$content['text']);}
 }
 public function testTestModeRecipientAndSubjectRemainSenderResponsibilities():void
 {
  $sent=[];$sender=new ApplicationReviewEmailSender(['test_mode'=>true,'test_email'=>'chuck@example.test','app_url'=>'https://gateway.example.test'],function(...$args)use(&$sent){$sent[]=$args;return true;});self::assertTrue($sender->sendSubmission($this->review('pending_review')));self::assertSame('chuck@example.test',$sent[0][0]);self::assertSame('Application Review Submitted — Alpha Community',$sent[0][1]);self::assertStringContainsString('<!doctype html>',$sent[0][2]);self::assertStringContainsString('NPM GATEWAY',$sent[0][3]);$missing=new ApplicationReviewEmailSender(['test_mode'=>true,'test_email'=>'','app_url'=>'https://gateway.example.test'],fn()=>self::fail('Delivery must not run.'));self::assertFalse($missing->sendSubmission($this->review('pending_review')));
 }
 public function testRendererFailureFailsClosedBeforeTransport():void
 {
  $renderer=new class extends GatewayEmailRenderer{public function render(GatewayEmailMessage $message):array{throw new \RuntimeException('private renderer detail');}};$delivered=false;$sender=new ApplicationReviewEmailSender(['test_mode'=>true,'test_email'=>'chuck@example.test','app_url'=>'https://gateway.example.test'],function()use(&$delivered){$delivered=true;return true;},$renderer);self::assertFalse($sender->sendSubmission($this->review('pending_review')));self::assertFalse($delivered);
 }
 private function review(string $status):array{return ['public_id'=>str_repeat('R',26),'property_name'=>'Alpha Community','property_slug'=>'alpha','prospect_name'=>'Prospect Person','submitted_by_name'=>'Manager User','submitted_at'=>'2026-08-05 01:34:00','manager_comments'=>'Manager <comments>','status'=>$status,'reviewed_by_name'=>'Reviewer User','reviewed_at'=>'2026-08-05 02:03:00','reviewer_comments'=>'Reviewer <basis>'];}
}
