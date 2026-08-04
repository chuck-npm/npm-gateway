<?php
declare(strict_types=1);
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$rows=$notice['recipients'];$recipientCount=count($rows);$ack=count(array_filter($rows,fn($r)=>$r['acknowledged_at']!==null));$sent=count(array_filter($rows,fn($r)=>$r['email_status']==='sent'));$failed=count(array_filter($rows,fn($r)=>in_array($r['email_status'],['failed','skipped_no_email'],true)));
$rich=(string)($notice['payload']['rich_message_html']??'<p>'.$e($notice['payload']['message']??'').'</p>');foreach((array)($notice['assets']??[]) as $asset)if($asset['asset_role']==='embedded_image')$rich=str_replace('gateway-storage:'.$asset['public_id'],'/storage/'.$asset['public_id'].'/image',$rich);$rich=(new \NpmGateway\Services\RichTextSanitizer())->renderImageWidths($rich);
$attachments=array_values(array_filter((array)($notice['assets']??[]),fn($a)=>$a['asset_role']==='attachment'));
ob_start();
?>
<p><a href="/company-notices">Company Notices</a></p><?php if($success): ?><div class="alert alert-success"><?= $e($success) ?></div><?php endif; ?>
<h1><?= $e($notice['title']) ?></h1>
<div class="gateway-card"><div class="gateway-card__body">
 <div class="gateway-notice-rich-body"><?= $rich ?></div>
 <dl><dt>Published by</dt><dd><?= $e($notice['published_by']) ?></dd><dt>Published</dt><dd><?= $e(\NpmGateway\Support\CompanyNoticePresentation::publishedAt((string)$notice['published_at'])) ?></dd><dt>Priority</dt><dd><?= $e(ucfirst($notice['priority'])) ?></dd><dt>Requires acknowledgment</dt><dd><?= $notice['requires_acknowledgment']?'Yes':'No' ?></dd><dt>Audience</dt><dd><?= $e($notice['payload']['audience_label']??'All Active Gateway Users') ?></dd><dt>Recipients</dt><dd><?= $recipientCount ?></dd><dt>Email sent</dt><dd><?= $sent ?></dd><dt>Email failed/skipped</dt><dd><?= $failed ?></dd><dt>Acknowledged</dt><dd><?= $ack ?></dd><dt>Outstanding</dt><dd><?= $notice['requires_acknowledgment']?$recipientCount-$ack:0 ?></dd></dl>
 <?php if($attachments): ?><section aria-labelledby="published-attachments-heading"><h2 id="published-attachments-heading"><?php require dirname(__DIR__).'/components/icon-paperclip.php'; ?> <?= $e(\NpmGateway\Support\CompanyNoticePresentation::attachmentHeading(count($attachments))) ?></h2><ul class="list-unstyled"><?php foreach($attachments as $asset): ?><li class="mb-3"><strong><?= $e($asset['display_filename']) ?></strong><br><span class="text-secondary"><?= $e(\NpmGateway\Support\CompanyNoticePresentation::typeLabel($asset)) ?> · <?= $e(\NpmGateway\Support\CompanyNoticePresentation::fileSize((int)$asset['byte_size'])) ?></span><br><a href="/storage/<?= $e($asset['public_id']) ?>" aria-label="Download <?= $e($asset['display_filename']) ?>">Download</a></li><?php endforeach; ?></ul></section><?php endif; ?>
</div></div>
<table class="table mt-4"><thead><tr><th>Employee</th><th>Username</th><th>User Status</th><th>Business Email Status</th><th>First Viewed</th><th>Acknowledged</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= $e($r['employee']) ?></td><td><?= $e($r['username']) ?></td><td><?= $e($r['status']) ?></td><td><?= $e($r['email_status']) ?></td><td><?= $e($r['first_viewed_at']??'—') ?></td><td><?= $e($r['acknowledged_at']??'—') ?></td></tr><?php endforeach; ?></tbody></table>
<?php
$contentHtml=(string)ob_get_clean();$pageTitle=$notice['title'].' — Company Notices';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/company-notices',dirname(__DIR__,3));$navbarUserLabel=$user->displayName;require dirname(__DIR__).'/layouts/app.php';
