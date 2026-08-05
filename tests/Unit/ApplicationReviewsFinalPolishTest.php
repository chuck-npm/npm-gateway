<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class ApplicationReviewsFinalPolishTest extends TestCase
{
 public function testManagerQueueUsesFixedStatusPriorityAndRelevantTimestamps():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Repositories/ApplicationReviewRepository.php');
  foreach(["CASE r.status WHEN 'pending_review' THEN 0 WHEN 'approved' THEN 1 WHEN 'denied' THEN 2 ELSE 3 END","CASE WHEN r.status='pending_review' THEN r.submitted_at END DESC","CASE WHEN r.status IN ('approved','denied') THEN COALESCE(r.reviewed_at,r.updated_at) END DESC", "in_array(\$status,['pending_review','approved','denied'],true)",' AND r.status=?',' AND r.prospect_name LIKE ?','WHERE r.property_id=?'] as $required)self::assertStringContainsString($required,$source);
  self::assertStringNotContainsString('ORDER BY $status',$source);
 }
 public function testMixedQueueContractOrdersStatusesAndTimestamps():void
 {
  $rows=[['id'=>1,'status'=>'approved','submitted_at'=>'2026-08-05 12:00:00','reviewed_at'=>'2026-08-05 13:00:00','updated_at'=>'2026-08-05 13:00:00'],['id'=>2,'status'=>'pending_review','submitted_at'=>'2026-08-05 10:00:00','reviewed_at'=>null,'updated_at'=>'2026-08-05 10:00:00'],['id'=>3,'status'=>'denied','submitted_at'=>'2026-08-05 14:00:00','reviewed_at'=>'2026-08-05 17:00:00','updated_at'=>'2026-08-05 17:00:00'],['id'=>4,'status'=>'pending_review','submitted_at'=>'2026-08-05 11:00:00','reviewed_at'=>null,'updated_at'=>'2026-08-05 11:00:00'],['id'=>5,'status'=>'approved','submitted_at'=>'2026-08-05 09:00:00','reviewed_at'=>'2026-08-05 16:00:00','updated_at'=>'2026-08-05 16:00:00'],['id'=>6,'status'=>'denied','submitted_at'=>'2026-08-05 15:00:00','reviewed_at'=>'2026-08-05 18:00:00','updated_at'=>'2026-08-05 18:00:00']];
  $priority=['pending_review'=>0,'approved'=>1,'denied'=>2];usort($rows,static function(array $a,array $b)use($priority):int{$group=$priority[$a['status']]<=>$priority[$b['status']];if($group!==0)return $group;$aTime=$a['status']==='pending_review'?$a['submitted_at']:($a['reviewed_at']??$a['updated_at']);$bTime=$b['status']==='pending_review'?$b['submitted_at']:($b['reviewed_at']??$b['updated_at']);return $bTime<=>$aTime;});
  self::assertSame([4,2,5,1,6,3],array_column($rows,'id'));
  foreach(['pending_review','approved','denied'] as $status){$filtered=array_values(array_filter($rows,static fn(array $row):bool=>$row['status']===$status));self::assertSame(array_values(array_filter(array_column($rows,'id'),static fn(int $id):bool=>in_array($id,array_column($filtered,'id'),true))),array_column($filtered,'id'));}
 }
 public function testDetailHierarchyAndSharedTimelineRemainSemanticAndResponsive():void
 {
  $root=dirname(__DIR__,2);$header=(string)file_get_contents($root.'/resources/views/components/page-header.php');self::assertLessThan(strpos($header,'gateway-page-header__status'),strpos($header,'gateway-page-header__title'));self::assertLessThan(strpos($header,'gateway-page-header__description'),strpos($header,'gateway-page-header__status'));
  $manager=(string)file_get_contents($root.'/resources/views/community-actions/application-reviews/show.php');foreach(["\$heading='Application Review'","\$description=\$context->propertyDisplayName","\$statusHtml=(string)ob_get_clean()"] as $required)self::assertStringContainsString($required,$manager);
  $corporate=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/show.php');foreach(["\$heading='Application Reviews'","\$description='Review prospect submissions from all communities.'","\$statusHtml=(string)ob_get_clean()",'breadcrumb.php',"if(\$success!=='')"] as $required)self::assertStringContainsString($required,$corporate);
  $detail=(string)file_get_contents($root.'/resources/views/components/application-review-detail.php');foreach(['Current Status','<ol class="gateway-review-timeline__events">','<time datetime=','gateway-review-timeline__label','gateway-review-timeline__meta','gateway-review-timeline__comments','nl2br($e('] as $required)self::assertStringContainsString($required,$detail);foreach(['status-badge.php','gateway-review-status'] as $forbidden)self::assertStringNotContainsString($forbidden,$detail);
  $css=(string)file_get_contents($root.'/public/assets/css/gateway.css');foreach(['.gateway-review-timeline__event { position:relative;padding:0 0 1.75rem 1.5rem;','.gateway-review-timeline__comments { margin:.75rem 0 0;white-space:normal;overflow-wrap:anywhere;','@media (max-width:575.98px)','overflow-wrap:anywhere'] as $required)self::assertStringContainsString($required,$css);
 }
}
