<?php
declare(strict_types=1);
?>
<div class="gateway-document-viewer" data-document-viewer hidden>
 <div class="gateway-document-viewer__backdrop" data-document-viewer-close></div>
 <section class="gateway-document-viewer__dialog" role="dialog" aria-modal="true" aria-labelledby="gateway-document-viewer-title" aria-describedby="gateway-document-viewer-description" tabindex="-1">
  <header class="gateway-document-viewer__header">
   <div class="gateway-document-viewer__identity"><h2 id="gateway-document-viewer-title" data-document-viewer-title>Document</h2><p class="gateway-document-viewer__filename" id="gateway-document-viewer-description" data-document-viewer-filename></p></div>
   <nav class="gateway-document-viewer__actions" aria-label="Document actions"><a class="btn gateway-button gateway-button--secondary" data-document-viewer-new-tab target="_blank" rel="noopener noreferrer">Open in New Tab <span class="visually-hidden">(opens in a new tab)</span></a><a class="btn gateway-button gateway-button--secondary" data-document-viewer-download>Download</a><button class="btn gateway-button gateway-button--secondary" type="button" data-document-viewer-close>Close</button></nav>
  </header>
  <div class="gateway-document-viewer__canvas">
   <p class="gateway-document-viewer__state" data-document-viewer-loading role="status">Loading document…</p>
   <div class="gateway-document-viewer__state" data-document-viewer-failure hidden><p data-document-viewer-failure-message>The document could not be displayed.</p><p>Use Open in New Tab or Download to access the document.</p></div>
   <div class="gateway-document-viewer__pdf" data-document-viewer-pdf hidden><iframe title="Document content" data-document-viewer-frame></iframe><p class="gateway-document-viewer__pdf-fallback">If your browser cannot display this PDF, use Open in New Tab or Download.</p></div>
   <div class="gateway-document-viewer__image" data-document-viewer-image hidden><img data-document-viewer-img alt="Gateway document"></div>
  </div>
 </section>
</div>
