<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use NpmGateway\Services\RmCorrectionStatus;
use NpmGateway\Support\GatewayDateTimeFormatter;
use PHPUnit\Framework\TestCase;
final class RmCorrectionsQueuePresentationTest extends TestCase
{
 public function testManagerAndCorporateUseIdenticalCompactAccessibleSummaryPattern():void
 {
  [$manager,$corporate]=$this->views();foreach([$manager,$corporate]as$view){self::assertStringContainsString('gateway-review-summary d-flex flex-wrap gap-2',$view);self::assertStringContainsString('role="status" aria-label="RM Corrections status summary"',$view);self::assertStringContainsString('$statusLabel=$counts[$s].\' \'.$l',$view);self::assertStringContainsString('$statusType=$badges[$s]',$view);self::assertStringContainsString('status-badge.php',$view);self::assertStringNotContainsString('gateway-summary-grid',$view);self::assertStringNotContainsString('<div class="card"><div class="card-body">',$view);self::assertStringNotContainsString('<style',$view);self::assertStringNotContainsString('<script',$view);}self::assertSame(substr_count($manager,'gateway-review-summary'),substr_count($corporate,'gateway-review-summary'));
 }
 public function testCentralStatusMetadataDrivesSummaryAndRows():void
 {
  self::assertSame(['pending_review'=>'Pending Review','approved'=>'Approved','denied'=>'Denied','more_information_needed'=>'More Information Needed'],RmCorrectionStatus::LABELS);self::assertSame(['pending_review'=>'neutral','approved'=>'success','denied'=>'danger','more_information_needed'=>'warning'],RmCorrectionStatus::BADGES);foreach($this->views()as$view){self::assertStringContainsString('$labels=RmCorrectionStatus::LABELS',$view);self::assertStringContainsString('$badges=RmCorrectionStatus::BADGES',$view);self::assertSame(2,substr_count($view,'status-badge.php'));self::assertStringNotContainsString('><?=$e($labels[$r[\'status\']])?></td>',$view);}
 }
 public function testListsUseOneHumanReadableDateTimeFormatterForBothColumns():void
 {
  self::assertSame('August 7, 2026 at 12:35 AM',GatewayDateTimeFormatter::format('2026-08-07 00:35:17'));self::assertSame('not-a-date',GatewayDateTimeFormatter::format('not-a-date'));foreach($this->views()as$view){self::assertSame(2,substr_count($view,'GatewayDateTimeFormatter::format('));self::assertStringContainsString('format($r[\'submitted_at\'])',$view);self::assertStringContainsString('format($r[\'updated_at\'])',$view);self::assertStringNotContainsString('<?=$e($r[\'submitted_at\'])?>',$view);self::assertStringNotContainsString('<?=$e($r[\'updated_at\'])?>',$view);self::assertStringNotContainsString("format('Y-m-d H:i:s')",$view);}
 }
 public function testApprovedColumnsFiltersAndActionsRemainIntact():void
 {
  [$manager,$corporate]=$this->views();foreach(['Lot / Address','Tenant','Submitted','Status','Last Updated','Action','name="search"','name="status"','Apply Filters','>View<']as$text)self::assertStringContainsString($text,$manager);foreach(['Property','Lot / Address','Tenant','Submitted By','Submitted','Status','Last Updated','Action','name="search"','name="status"','name="property"','Apply Filters',"?'Review':'View'"]as$text)self::assertStringContainsString($text,$corporate);foreach([$manager,$corporate]as$view){self::assertStringNotContainsString('Correction Request</th>',$view);self::assertStringNotContainsString('Comments</th>',$view);}
 }
 private function views():array{$root=dirname(__DIR__,2).'/resources/views';return[(string)file_get_contents($root.'/community-actions/rm-corrections/index.php'),(string)file_get_contents($root.'/corporate/rm-corrections/index.php')];}
}
