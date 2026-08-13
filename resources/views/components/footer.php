<?php

declare(strict_types=1);

$footerText = isset($footerText) ? (string) $footerText : 'NPM Gateway';
$processingOverlayPath = dirname(__DIR__, 3) . '/public/assets/js/processing-overlay.js';
$processingOverlayVersion = is_file($processingOverlayPath) ? (string) filemtime($processingOverlayPath) : '1';
$flyerUploadPath = dirname(__DIR__, 3) . '/public/assets/js/marketing-flyer-upload.js';
$flyerUploadVersion = is_file($flyerUploadPath) ? (string) filemtime($flyerUploadPath) : '1';
?>
<footer class="gateway-footer">
    <div class="container gateway-footer__inner">
        <small><?= htmlspecialchars($footerText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
<script type="module" src="/assets/js/phone-mask.js"></script>
<script type="module" src="/assets/js/employee-username.js"></script>
<script type="module" src="/assets/js/processing-overlay.js?v=<?= rawurlencode($processingOverlayVersion) ?>"></script>
<script type="module" src="/assets/js/character-counter.js"></script>
<?php if(!empty($quillAssets)): ?><script src="/assets/vendor/quill/2.0.3/quill.js"></script><script type="module" src="/assets/js/company-notice-editor.js"></script><?php endif; ?>
<?php if(!empty($rmAuditAssets)): ?><script src="/assets/vendor/quill/2.0.3/quill.js"></script><script type="module" src="/assets/js/rm-audit-editor.js"></script><?php endif; ?>
<?php if(!empty($supplyOrderAssets)): ?><script src="/assets/vendor/quill/2.0.3/quill.js"></script><script type="module" src="/assets/js/supply-order-editor.js"></script><?php endif; ?>
<?php if(!empty($companyNoticeDraftAssets)): ?><script type="module" src="/assets/js/company-notice-draft-discard.js"></script><?php endif; ?>
<?php if(!empty($creditCardPurchaseAssets)): ?><script type="module" src="/assets/js/credit-card-receipt-documentation.js"></script><?php endif; ?>
<?php if(!empty($documentViewerAssets)): ?><script type="module" src="/assets/js/document-viewer.js"></script><?php endif; ?>
<?php if(!empty($rmAuditReportAssets)): ?><script type="module" src="/assets/js/rm-audit-report.js"></script><?php endif; ?>
<?php if(!empty($flyerUploadAssets)): ?><script src="/assets/js/marketing-flyer-upload.js?v=<?= rawurlencode($flyerUploadVersion) ?>" defer></script><?php endif; ?>
</body>
</html>
