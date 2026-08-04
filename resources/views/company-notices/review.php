<?php
declare(strict_types=1);
$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$reviewHtml=(string)$data['rich_message_html'];foreach($reviewAssets??[] as $asset)if(($asset['asset_role']??'')==='embedded_image')$reviewHtml=str_replace('gateway-storage:'.$asset['public_id'],'/company-notices/uploads/'.$asset['public_id'].'/preview?compose_context='.rawurlencode((string)$data['compose_context']),$reviewHtml);$reviewHtml=(new \NpmGateway\Services\RichTextSanitizer())->renderImageWidths($reviewHtml);
ob_start();
?>
<p>Dashboard · Company Notices · Create Notice · Review</p>
<h1>Review Company Notice</h1>
<div class="alert alert-warning">Publishing will immediately create Gateway notifications and send company announcement emails to eligible active users.</div>
<section class="gateway-card">
 <div class="gateway-card__body">
  <dl>
   <dt>Title</dt><dd><?= $e($data['title']) ?></dd>
   <dt>Category</dt><dd>General Company Notice</dd>
   <dt>Priority</dt><dd><?= $e(ucfirst($data['priority'])) ?></dd>
   <dt>Requires Acknowledgment</dt><dd><?= $data['requires_acknowledgment']?'Yes':'No' ?></dd>
   <dt>Audience</dt><dd>All Active Gateway Users (<?= $recipientCount ?> eligible)</dd>
  </dl>
  <h2>In-app notification preview</h2>
  <div class="gateway-card"><div class="gateway-card__body gateway-notice-rich-body"><?= $reviewHtml ?></div></div>
  <?php $ordinary=array_values(array_filter($reviewAssets??[],fn($a)=>$a['asset_role']==='attachment'));$total=array_sum(array_map(fn($a)=>(int)$a['byte_size'],$ordinary)); ?>
  <?php if($ordinary): ?><section class="mt-4" aria-labelledby="review-attachments-heading"><h2 id="review-attachments-heading"><?php require dirname(__DIR__).'/components/icon-paperclip.php'; ?> <?= $e(\NpmGateway\Support\CompanyNoticePresentation::attachmentHeading(count($ordinary))) ?></h2><p><?= count($ordinary) ?> <?= count($ordinary)===1?'file':'files' ?> · <?= $e(\NpmGateway\Support\CompanyNoticePresentation::fileSize($total)) ?> total</p><ul class="list-unstyled"><?php foreach($ordinary as $asset): ?><li class="mb-3"><strong><?= $e($asset['display_filename']) ?></strong><br><span class="text-secondary"><?= $e(\NpmGateway\Support\CompanyNoticePresentation::typeLabel($asset)) ?> · <?= $e(\NpmGateway\Support\CompanyNoticePresentation::fileSize((int)$asset['byte_size'])) ?></span><br><a href="/company-notices/uploads/<?= $e($asset['public_id']) ?>/download?compose_context=<?= $e($data['compose_context']) ?>" aria-label="Download <?= $e($asset['display_filename']) ?>">Download</a></li><?php endforeach; ?></ul></section><?php endif; ?>
  <h2 class="mt-4">Email announcement preview</h2>
  <p><strong>NPM GATEWAY · COMPANY ANNOUNCEMENT</strong></p>
  <h3><?= $e($data['title']) ?></h3>
  <p><?= nl2br($e($data['message']),false) ?></p>
 </div>
 <footer class="gateway-card__footer d-flex justify-content-between">
  <div class="d-flex gap-2"><a class="btn btn-secondary" href="/company-notices/create">Back to Edit</a><button class="btn btn-secondary" type="button" data-discard-open>Discard Draft</button></div>
  <form method="post" action="/company-notices/publish" data-processing-form data-processing-message="Publishing company notice and sending emails…">
   <input type="hidden" name="_token" value="<?= $e($csrfToken) ?>">
   <input type="hidden" name="review_token" value="<?= $e($token) ?>">
   <button class="btn btn-primary">Publish Company Notice</button>
  </form>
 </footer>
</section>
<?php $composeContext=(string)$data['compose_context'];require __DIR__.'/_discard-dialog.php'; ?>
<?php
$contentHtml=(string)ob_get_clean();
$pageTitle='Review Company Notice — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/company-notices',dirname(__DIR__,3));
$navbarUserLabel=$user->displayName;
$companyNoticeDraftAssets=true;
require dirname(__DIR__).'/layouts/app.php';
