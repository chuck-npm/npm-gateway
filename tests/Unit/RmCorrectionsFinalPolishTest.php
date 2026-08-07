<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use NpmGateway\Notifications\GatewayEmailRenderer;
use NpmGateway\Notifications\RmCorrectionEmailSender;
use PHPUnit\Framework\TestCase;
final class RmCorrectionsFinalPolishTest extends TestCase
{
 public function testBothDetailsShareProminentCentralStatusAndBusinessRecordComponent():void
 {
  [$manager,$corporate,$detail]=$this->views();foreach([$manager,$corporate]as$view){self::assertStringContainsString('RmCorrectionStatus::LABELS',$view);self::assertStringContainsString('RmCorrectionStatus::BADGES',$view);self::assertStringContainsString('status-badge.php',$view);self::assertStringContainsString('rm-correction-detail.php',$view);self::assertStringContainsString('Back to RM Corrections',$view);self::assertStringNotContainsString('<style',$view);self::assertStringNotContainsString('<script',$view);}foreach(['Property','Lot / Address','Tenant','Submitted By','Submitted At','Current Status','Last Updated','Correction Request','Comments','History','<time datetime=']as$text)self::assertStringContainsString($text,$detail);self::assertSame(1,substr_count($detail,'<h3>Correction Request</h3>'));self::assertSame(1,substr_count($detail,'<h3>Comments</h3>'));
 }
 public function testManagerAndCorporateActionsRemainStateScoped():void
 {
  [$manager,$corporate]=$this->views();self::assertStringContainsString("status']==='more_information_needed'",$manager);self::assertStringContainsString('Respond to Request',$manager);self::assertStringContainsString('gateway-button--primary',$manager);self::assertStringContainsString('btn btn-secondary',$manager);self::assertStringContainsString("status']==='pending_review'",$corporate);foreach(['Review Decision','value="approved"','value="denied"','value="more_information_needed"','gateway-review-decision-actions__cancel']as$text)self::assertStringContainsString($text,$corporate);self::assertSame(1,substr_count($corporate,'Review Decision'));
 }
 public function testTimelineAndNarrativesHaveSharedSafeWrappingAndSpacing():void
 {
  $root=dirname(__DIR__,2);$css=(string)file_get_contents($root.'/public/assets/css/gateway.css');foreach(['.gateway-review-record h3 { margin:1.5rem 0 .5rem;','.gateway-review-record h3 + .gateway-review-timeline__comments','white-space:pre-wrap','overflow-wrap:anywhere','.gateway-review-timeline__event { position:relative;padding:0 0 1.75rem 1.5rem']as$rule)self::assertStringContainsString($rule,$css);$detail=(string)file_get_contents($root.'/resources/views/components/rm-correction-detail.php');foreach(['more_information_needed','manager_responded','nl2br($e($h[\'comments\'])']as$text)self::assertStringContainsString($text,$detail);
 }
 public function testDecisionEmailIncludesReviewerMetadataInBrandedMultipartOutput():void
 {
  $sent=[];$sender=new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'test@example.com','app_url'=>'https://gateway.test'],function(...$args)use(&$sent){$sent=$args;return true;},new GatewayEmailRenderer());$r=['public_id'=>str_repeat('A',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','lot_address'=>'88B','tenant_name'=>'Mary Keller','submitted_by_name'=>'Amanda Watson','submitted_at'=>'2026-08-07 09:00:00','manager_email'=>'manager@example.com','status'=>'approved','reviewed_by_name'=>'Corporate Reviewer','reviewed_at'=>'2026-08-07 11:00:00'];self::assertTrue($sender->decision($r,'Late fee removed.'));foreach(['NPM Gateway','RM CORRECTIONS','Approved','Property','Lot / Address','Tenant','Submitted By','Reviewed By','Corporate Reviewer','Reviewed At','Comments','Late fee removed.','View RM Correction']as$text){self::assertStringContainsString($text,$sent[2]);self::assertStringContainsString($text,$sent[3]);}self::assertStringContainsString('/community-actions/pine-hill/rm-corrections/'.str_repeat('A',26),$sent[3]);
 }
 private function views():array{$root=dirname(__DIR__,2).'/resources/views';return[(string)file_get_contents($root.'/community-actions/rm-corrections/show.php'),(string)file_get_contents($root.'/corporate/rm-corrections/show.php'),(string)file_get_contents($root.'/components/rm-correction-detail.php')];}
}
