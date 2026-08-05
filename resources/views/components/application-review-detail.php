<?php
declare(strict_types=1);
$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$components=__DIR__;
$statusLabel=['pending_review'=>'Pending Review','approved'=>'Approved','denied'=>'Denied'][$review['status']];
$statusType=['pending_review'=>'neutral','approved'=>'success','denied'=>'danger'][$review['status']];
$eventLabels=['submitted'=>'Submitted','approved'=>'Approved','denied'=>'Denied'];
?>
<section class="card gateway-detail-card gateway-review-record" aria-labelledby="review-details-heading">
 <div class="card-body">
  <h2 id="review-details-heading">Review Details</h2>
  <dl class="row gateway-review-record__fields">
   <dt class="col-sm-4 gateway-review-record__label">Prospect</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e($review['prospect_name'])?></dd>
   <dt class="col-sm-4 gateway-review-record__label">Property</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e($review['property_name'])?></dd>
   <dt class="col-sm-4 gateway-review-record__label">Submitted By</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e($review['submitted_by_name'])?></dd>
   <dt class="col-sm-4 gateway-review-record__label">Submitted At</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e((new DateTimeImmutable($review['submitted_at']))->format('F j, Y \a\t g:i A'))?></dd>
   <dt class="col-sm-4 gateway-review-record__label">RM documents confirmation</dt><dd class="col-sm-8 gateway-review-record__value">Confirmed</dd>
   <dt class="col-sm-4 gateway-review-record__label">Manager Comments</dt><dd class="col-sm-8 gateway-review-record__value"><?=($review['manager_comments']??'')!==''?nl2br($e($review['manager_comments']),false):'None'?></dd>
   <dt class="col-sm-4 gateway-review-record__label">Current Status</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e($statusLabel)?></dd>
   <?php if($review['reviewed_at']!==null):?>
   <dt class="col-sm-4 gateway-review-record__label">Reviewed By</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e($review['reviewed_by_name'])?></dd>
   <dt class="col-sm-4 gateway-review-record__label">Reviewed At</dt><dd class="col-sm-8 gateway-review-record__value"><?=$e((new DateTimeImmutable($review['reviewed_at']))->format('F j, Y \a\t g:i A'))?></dd>
   <dt class="col-sm-4 gateway-review-record__label">Review Notes</dt><dd class="col-sm-8 gateway-review-record__value"><?=nl2br($e($review['reviewer_comments']),false)?></dd>
   <?php endif;?>
  </dl>
 </div>
</section>
<section class="gateway-review-timeline" aria-labelledby="review-history-heading">
 <h2 id="review-history-heading">History</h2>
 <ol class="gateway-review-timeline__events">
  <?php foreach($review['history'] as $event):?>
  <li class="gateway-review-timeline__event">
   <strong class="gateway-review-timeline__label"><?=$e($eventLabels[$event['event_type']]??'Review Event')?></strong>
   <div class="gateway-review-timeline__meta">
    <time datetime="<?=$e((new DateTimeImmutable($event['created_at']))->format(DateTimeInterface::ATOM))?>"><?=$e((new DateTimeImmutable($event['created_at']))->format('F j, Y \a\t g:i A'))?></time>
    <span><?=$e($event['acted_by_name'])?></span>
   </div>
   <?php if(($event['comments']??'')!==''):?><p class="gateway-review-timeline__comments"><?=nl2br($e($event['comments']),false)?></p><?php endif;?>
  </li>
  <?php endforeach;?>
 </ol>
</section>
