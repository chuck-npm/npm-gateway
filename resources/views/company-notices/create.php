<?php
declare(strict_types=1);
$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$old=static fn(string $key,string $default=''):string=>(string)($input[$key]??$default);
$invalid=static fn(string $key):string=>isset($errors[$key])?' is-invalid':'';
ob_start();
?>
<p><a href="/company-notices">Company Notices</a></p>
<header>
 <h1>Create Company Notice</h1>
 <p>Compose an official company communication for Gateway users.</p>
</header>
<?php if(($draftNotice??'')!==''): ?><div class="alert alert-info" role="status"><?= $e($draftNotice) ?></div><?php endif; ?>
<?php if(($draftError??'')!==''): ?><div class="alert alert-warning" role="alert"><?= $e($draftError) ?></div><?php endif; ?>
<form method="post" action="/company-notices/preview" class="gateway-card gateway-property-form-card" data-processing-form data-processing-message="Preparing company notice preview…">
 <div class="gateway-card__body">
  <input type="hidden" name="_token" value="<?= $e($csrfToken) ?>">
  <input type="hidden" name="compose_context" value="<?= $e($composeContext??'') ?>">

  <section class="gateway-form-section" aria-labelledby="notice-information-heading">
   <h2 class="gateway-form-section-title" id="notice-information-heading">Notice Information</h2>
   <div class="row gateway-form-grid">
    <div class="col-12">
     <label class="form-label" for="company-notice-title">Title</label>
     <input class="form-control w-100<?= $invalid('title') ?>" id="company-notice-title" name="title" maxlength="200" required value="<?= $e($old('title')) ?>"<?= isset($errors['title'])?' aria-describedby="company-notice-title-error"':'' ?>>
     <?php if(isset($errors['title'])): ?><div class="invalid-feedback" id="company-notice-title-error"><?= $e($errors['title']) ?></div><?php endif; ?>
    </div>
    <div class="col-12"><p class="mb-0">Category: <strong>General Company Notice</strong></p></div>
   </div>
  </section>

  <section class="gateway-form-section" aria-labelledby="notice-audience-heading">
   <h2 class="gateway-form-section-title" id="notice-audience-heading">Audience</h2>
   <p class="mb-0">All Active Gateway Users</p>
  </section>

  <section class="gateway-form-section" aria-labelledby="notice-message-heading">
   <h2 class="gateway-form-section-title" id="notice-message-heading">Message</h2>
   <div class="row gateway-form-grid">
    <div class="col-12">
     <label class="form-label" for="company-notice-message">Notice Message</label>
     <input type="hidden" id="company-notice-rich-message" name="rich_message_html" value="<?= $e($old('rich_message_html')) ?>">
     <div class="gateway-company-notice-editor">
      <div class="gateway-notice-editor" id="company-notice-editor" data-image-upload="/company-notices/uploads/images" aria-label="Notice Message" aria-describedby="company-notice-message-help"></div>
      <button class="gateway-image-resize-handle" id="company-notice-image-resize-handle" type="button" aria-label="Resize selected image" hidden></button>
      <div class="gateway-image-resize-controls" id="company-notice-image-resize-controls" hidden>
       <button class="btn btn-secondary" id="company-notice-image-decrease" type="button" aria-label="Decrease image size">&minus;</button>
       <output id="company-notice-image-width" aria-live="polite">Image width: 25%</output>
       <button class="btn btn-secondary" id="company-notice-image-increase" type="button" aria-label="Increase image size">+</button>
       <div class="btn-group" role="group" aria-label="Image alignment">
        <button class="btn btn-secondary" type="button" data-company-notice-image-align="left" aria-pressed="true">Left</button>
        <button class="btn btn-secondary" type="button" data-company-notice-image-align="center" aria-pressed="false">Center</button>
        <button class="btn btn-secondary" type="button" data-company-notice-image-align="right" aria-pressed="false">Right</button>
       </div>
       <button class="btn btn-secondary mt-2" id="company-notice-image-preview-retry" type="button" hidden>Retry Preview</button>
       <span class="form-text">Drag the selected image handle or use the buttons. Press Escape to deselect.</span>
      </div>
      <textarea class="form-control w-100 gateway-notice-message<?= $invalid('message') ?>" id="company-notice-message" name="message" maxlength="10000" rows="9" required aria-describedby="company-notice-message-help<?= isset($errors['message'])?' company-notice-message-error':'' ?>"><?= $e($old('message')) ?></textarea>
     </div>
     <div class="form-text gateway-notice-editor-help" id="company-notice-message-help">Maximum 10,000 characters.</div>
     <?php if(isset($errors['message'])): ?><div class="invalid-feedback" id="company-notice-message-error"><?= $e($errors['message']) ?></div><?php endif; ?>
    </div>
   </div>
  </section>

  <section class="gateway-form-section gateway-notice-attachments" aria-labelledby="notice-attachments-heading">
   <h2 class="gateway-form-section-title" id="notice-attachments-heading"><?php require dirname(__DIR__).'/components/icon-paperclip.php'; ?> Attachments</h2>
   <div class="form-text gateway-notice-attachment-help mb-3">
    <p>PDF, DOCX, XLSX, ZIP, JPG, PNG or WebP.</p>
    <p>Up to 10 combined files.</p>
    <p>100 MiB per file.</p>
   </div>
   <div class="gateway-upload-panel" id="company-notice-drop-zone">
    <p class="gateway-upload-panel__prompt mb-2">Drag files here to upload</p>
    <p class="form-text mb-2">or</p>
    <label class="btn btn-secondary" for="company-notice-attachments">Choose Files</label>
    <input class="visually-hidden" id="company-notice-attachments" type="file" multiple accept=".pdf,.docx,.xlsx,.zip,.jpg,.jpeg,.png,.webp" data-upload-url="/company-notices/uploads/attachments">
   </div>
   <div class="gateway-upload-totals" aria-label="Attachment totals">
    <p class="mb-0"><span id="company-notice-file-total">0 files selected (0 of 10)</span></p>
    <p class="mb-0"><span id="company-notice-space-total">0 B of 1,000 MiB used</span></p>
   </div>
   <div id="company-notice-upload-status" class="visually-hidden" role="status" tabindex="-1" aria-live="polite" aria-atomic="true"></div>
   <div class="table-responsive gateway-upload-list-wrap mt-3">
    <table class="table gateway-upload-list mb-0" id="company-notice-asset-list">
     <thead><tr><th scope="col">File</th><th scope="col">Size</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
     <tbody><?php foreach(($selectedAssets??[]) as $asset): if(($asset['asset_role']??'attachment')!=='attachment')continue; ?><tr data-public-id="<?= $e($asset['public_id']) ?>" data-byte-size="<?= (int)$asset['byte_size'] ?>"><td data-label="File"><span aria-hidden="true">📄</span> <?= $e($asset['display_filename']) ?></td><td data-label="Size"><?= $e(\NpmGateway\Support\CompanyNoticePresentation::fileSize((int)$asset['byte_size'])) ?></td><td data-label="Type"><?= $e(\NpmGateway\Support\CompanyNoticePresentation::typeLabel($asset)) ?></td><td data-label="Status"><span class="gateway-status gateway-status--success"><span aria-hidden="true">✓</span> Uploaded</span></td><td data-label="Action"><button class="btn btn-link gateway-upload-remove" type="button" aria-label="Remove <?= $e($asset['display_filename']) ?>"><span aria-hidden="true">🗑</span> Remove</button></td></tr><?php endforeach; ?></tbody>
    </table>
    <p class="gateway-upload-empty mb-0" id="company-notice-upload-empty">No files uploaded yet.</p>
   </div>
  </section>

  <section class="gateway-form-section" aria-labelledby="notice-publishing-heading">
   <h2 class="gateway-form-section-title" id="notice-publishing-heading">Publishing Options</h2>
   <div class="row gateway-form-grid">
    <div class="col-12 col-md-6">
     <label class="form-label" for="company-notice-priority">Priority</label>
     <select class="form-select<?= $invalid('priority') ?>" id="company-notice-priority" name="priority"<?= isset($errors['priority'])?' aria-describedby="company-notice-priority-error"':'' ?>>
      <?php foreach(['normal'=>'Normal','important'=>'Important','urgent'=>'Urgent'] as $value=>$label): ?>
       <option value="<?= $value ?>"<?= $old('priority','normal')===$value?' selected':'' ?>><?= $label ?></option>
      <?php endforeach; ?>
     </select>
     <?php if(isset($errors['priority'])): ?><div class="invalid-feedback" id="company-notice-priority-error"><?= $e($errors['priority']) ?></div><?php endif; ?>
    </div>
    <fieldset class="col-12"<?= isset($errors['requires_acknowledgment'])?' aria-describedby="company-notice-ack-error"':'' ?>>
     <legend class="form-label">Requires Acknowledgment</legend>
     <div class="d-flex flex-wrap gap-4">
      <div class="form-check"><input class="form-check-input" type="radio" id="company-notice-ack-yes" name="requires_acknowledgment" value="yes" required<?= $old('requires_acknowledgment','yes')==='yes'?' checked':'' ?>><label class="form-check-label" for="company-notice-ack-yes">Yes</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" id="company-notice-ack-no" name="requires_acknowledgment" value="no"<?= $old('requires_acknowledgment')==='no'?' checked':'' ?>><label class="form-check-label" for="company-notice-ack-no">No</label></div>
     </div>
     <?php if(isset($errors['requires_acknowledgment'])): ?><div class="invalid-feedback d-block" id="company-notice-ack-error"><?= $e($errors['requires_acknowledgment']) ?></div><?php endif; ?>
    </fieldset>
   </div>
  </section>
 </div>
 <footer class="gateway-card__footer d-flex justify-content-between">
  <div class="d-flex gap-2"><a class="btn btn-secondary" href="/company-notices">Cancel</a><button class="btn btn-secondary" type="button" data-discard-open>Discard Draft</button></div>
  <button class="btn btn-primary" type="submit">Review Notice</button>
 </footer>
</form>
<?php require __DIR__.'/_discard-dialog.php'; ?>
<?php
$contentHtml=(string)ob_get_clean();
$pageTitle='Create Company Notice — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/company-notices',dirname(__DIR__,3));
$navbarUserLabel=$user->displayName;
$quillAssets=true;
$companyNoticeDraftAssets=true;
require dirname(__DIR__).'/layouts/app.php';
