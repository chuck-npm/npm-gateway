<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class ApplicationReviewPresentationRefinementTest extends TestCase
{
 public function testGlobalCheckboxContractPreservesAllAccessibleStates():void
 {
  $root=dirname(__DIR__,2);$css=(string)file_get_contents($root.'/public/assets/css/gateway.css');
  foreach(['--gateway-control-border-strong: #53657a','--gateway-control-border-disabled: #98a2b3','.form-check-input[type="checkbox"]:not(:checked):not(:disabled):not(.is-invalid)','background-color:var(--gateway-surface)','border-color:var(--gateway-control-border-strong)','.form-check-input[type="checkbox"]:checked:not(.is-invalid)','background-color:var(--bs-primary)','.form-check-input[type="checkbox"]:focus-visible','outline:.15rem solid var(--gateway-gold)','.form-check-input[type="checkbox"]:disabled','border-color:var(--gateway-control-border-disabled)','opacity:.65'] as $required)self::assertStringContainsString($required,$css);
  self::assertStringNotContainsString('.gateway-property-access-matrix .form-check-input:not(:checked)',$css);
  self::assertStringNotContainsString('.form-check-input[type="radio"]',$css);
  foreach(['resources/views/admin/category-access.php','resources/views/admin/property-access.php','resources/views/community-actions/application-reviews/create.php'] as $file){$view=(string)file_get_contents($root.'/'.$file);self::assertStringContainsString('type="checkbox"',$view);self::assertStringNotContainsString('style=',$view);}
  $form=(string)file_get_contents($root.'/resources/views/community-actions/application-reviews/create.php');self::assertStringContainsString('id="rm_documents_confirmed"',$form);self::assertStringContainsString('for="rm_documents_confirmed"',$form);self::assertStringContainsString(' required',$form);
 }
 public function testCorporateSummaryAndPendingActionUseApprovedPresentation():void
 {
  $root=dirname(__DIR__,2);$view=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/index.php');
  foreach(['gateway-review-summary d-flex flex-wrap gap-2','Pending Review','Approved','Denied','btn btn-sm btn-outline-primary','>Review</a>','>View</a>','/corporate/application-reviews/'] as $required)self::assertStringContainsString($required,$view);
  self::assertStringContainsString("\$counts[\$key].' '.\$label",$view);self::assertStringNotContainsString('style=',$view);self::assertStringNotContainsString('<script',$view);
 }
 public function testSharedDetailHasProminentStatusAndSemanticTimeline():void
 {
  $root=dirname(__DIR__,2);$view=(string)file_get_contents($root.'/resources/views/components/application-review-detail.php');
 foreach(['Current Status','gateway-review-timeline','gateway-review-timeline__events','gateway-review-timeline__event','gateway-review-timeline__label','gateway-review-timeline__meta','<time datetime=','acted_by_name','created_at','comments','nl2br($e('] as $required)self::assertStringContainsString($required,$view);
  foreach(['property_id','user_id','employee_id','public_id','style=','<script'] as $forbidden)self::assertStringNotContainsString($forbidden,$view);
 }
 public function testSharedDetailRendersApprovedLabelsFieldsEscapingAndConditionalReviewData():void
 {
  $pending=$this->renderDetail('pending_review',null,[['event_type'=>'submitted','created_at'=>'2026-08-05 01:15:00','acted_by_name'=>'Chuck Lundquist','comments'=>'First <unsafe> line'."\n".'Second line']]);
  foreach(['gateway-review-record','gateway-review-record__fields','Prospect','Property','Submitted By','Submitted At','RM documents confirmation','Manager Comments','Current Status','<ol class="gateway-review-timeline__events">','<time datetime=','Submitted','Chuck Lundquist','First &lt;unsafe&gt; line<br>','Second line'] as $required)self::assertStringContainsString($required,$pending);
  foreach(['Reviewed By','Reviewed At','Review Notes','<ol>','<script','style=','name=','type="submit"','Delete','Edit'] as $forbidden)self::assertStringNotContainsString($forbidden,$pending);
  $approved=$this->renderDetail('approved','2026-08-05 02:03:00',[['event_type'=>'submitted','created_at'=>'2026-08-05 01:15:00','acted_by_name'=>'Chuck Lundquist','comments'=>'Submitted'],['event_type'=>'approved','created_at'=>'2026-08-05 02:03:00','acted_by_name'=>'Amanda Watson','comments'=>'Screening completed.']]);
  foreach(['Reviewed By','Reviewed At','Review Notes','Submitted','Approved','Amanda Watson','Screening completed.'] as $required)self::assertStringContainsString($required,$approved);
  $timeline=substr($approved,(int)strpos($approved,'gateway-review-timeline__events'));self::assertLessThan(strpos($timeline,'Amanda Watson'),strpos($timeline,'Chuck Lundquist'));
  $denied=$this->renderDetail('denied','2026-08-05 02:03:00',[['event_type'=>'denied','created_at'=>'2026-08-05 02:03:00','acted_by_name'=>'Reviewer','comments'=>'Basis']]);self::assertStringContainsString('Current Status',$denied);self::assertStringContainsString('Denied',$denied);
 }
 public function testPresentationCssIsScopedResponsiveAndLeavesCheckboxRulesUnchanged():void
 {
  $css=(string)file_get_contents(dirname(__DIR__,2).'/public/assets/css/gateway.css');foreach(['.gateway-review-record__fields','.gateway-review-record__label','.gateway-review-record__value','overflow-wrap:anywhere','.gateway-review-timeline__events','.gateway-review-timeline__event','@media (max-width:575.98px)'] as $required)self::assertStringContainsString($required,$css);
 }
 private function renderDetail(string $status,?string $reviewedAt,array $history):string
 {
  $review=['status'=>$status,'prospect_name'=>'Prospect Person','property_name'=>'Alpha Community','submitted_by_name'=>'Manager User','submitted_at'=>'2026-08-05 01:15:00','manager_comments'=>"Manager line one\nManager line two",'reviewed_at'=>$reviewedAt,'reviewed_by_name'=>$reviewedAt!==null?'Amanda Watson':null,'reviewer_comments'=>$reviewedAt!==null?'Reviewer basis':null,'history'=>$history];ob_start();require dirname(__DIR__,2).'/resources/views/components/application-review-detail.php';return (string)ob_get_clean();
 }
}
