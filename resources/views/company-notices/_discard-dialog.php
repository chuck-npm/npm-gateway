<?php declare(strict_types=1); ?>
<dialog class="gateway-card" id="company-notice-discard-dialog" aria-labelledby="company-notice-discard-title" aria-describedby="company-notice-discard-description" aria-modal="true">
 <form method="post" action="/company-notices/discard">
  <div class="gateway-card__body">
   <h2 id="company-notice-discard-title">Discard this draft?</h2>
   <div id="company-notice-discard-description">
    <p>All uploaded attachments and embedded images that have not been published will be permanently removed.</p>
    <p>This action cannot be undone.</p>
   </div>
   <p class="visually-hidden" role="status" aria-live="polite" data-discard-announcement></p>
   <input type="hidden" name="_token" value="<?= $e($csrfToken) ?>">
   <input type="hidden" name="compose_context" value="<?= $e($composeContext) ?>">
  </div>
  <footer class="gateway-card__footer d-flex gap-2 justify-content-end">
   <button class="btn btn-secondary" type="button" data-discard-cancel>Cancel</button>
   <button class="btn btn-secondary" type="submit">Discard Draft</button>
  </footer>
 </form>
</dialog>
