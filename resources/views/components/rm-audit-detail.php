<?php
declare(strict_types=1);
use NpmGateway\Services\RmAuditStatus;
use NpmGateway\Support\GatewayDateTimeFormatter;
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$eventLabels = ['submitted' => 'Submitted', 'completed' => 'Completed', 'returned' => 'Returned'];
?>
<section class="card gateway-detail-card"><div class="card-body">
<dl class="row">
<dt class="col-sm-4">Property</dt><dd class="col-sm-8"><?=$e($audit['property_name'])?></dd>
<dt class="col-sm-4">Tenant</dt><dd class="col-sm-8"><?=$e($audit['tenant_name'])?></dd>
<dt class="col-sm-4">Unit</dt><dd class="col-sm-8"><?=$e($audit['unit_identifier'])?></dd>
<dt class="col-sm-4">Submitted By</dt><dd class="col-sm-8"><?=$e($audit['submitted_by_name'])?></dd>
<dt class="col-sm-4">Submitted At</dt><dd class="col-sm-8"><?=$e(GatewayDateTimeFormatter::format($audit['submitted_at']))?></dd>
<dt class="col-sm-4">Current Status</dt><dd class="col-sm-8"><?=$e(RmAuditStatus::LABELS[$audit['status']])?></dd>
<dt class="col-sm-4">Last Updated</dt><dd class="col-sm-8"><?=$e(GatewayDateTimeFormatter::format($audit['updated_at']))?></dd>
<?php if ($audit['completed_at']): ?>
<dt class="col-sm-4">Completed By</dt><dd class="col-sm-8"><?=$e($audit['completed_by_name'])?></dd>
<dt class="col-sm-4">Completed At</dt><dd class="col-sm-8"><?=$e(GatewayDateTimeFormatter::format($audit['completed_at']))?></dd>
<?php endif; ?>
</dl>
<h2>Audit Findings</h2><div class="gateway-rich-content"><?=$audit['audit_findings_html']?></div>
</div></section>
<section class="gateway-review-timeline" aria-labelledby="rm-audit-history-heading">
<h2 id="rm-audit-history-heading">History</h2>
<ol class="gateway-review-timeline__events">
<?php foreach ($audit['history'] as $history): ?>
<li class="gateway-review-timeline__event">
<strong class="gateway-review-timeline__label"><?=$e($eventLabels[$history['event_type']] ?? 'Updated')?></strong>
<div class="gateway-review-timeline__meta">
<time datetime="<?=$e((new DateTimeImmutable($history['created_at']))->format(DateTimeInterface::ATOM))?>"><?=$e(GatewayDateTimeFormatter::format($history['created_at']))?></time>
<span><?=$e($history['actor_name'])?></span>
</div>
<?php if (trim((string) $history['comments']) !== ''): ?><p class="gateway-review-timeline__comments"><?=nl2br($e($history['comments']), false)?></p><?php endif; ?>
</li>
<?php endforeach; ?>
</ol>
</section>
