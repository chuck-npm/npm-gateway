<?php

declare(strict_types=1);

$footerText = isset($footerText) ? (string) $footerText : 'NPM Gateway';
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
<script type="module" src="/assets/js/processing-overlay.js"></script>
<?php if(!empty($quillAssets)): ?><script src="/assets/vendor/quill/2.0.3/quill.js"></script><script type="module" src="/assets/js/company-notice-editor.js"></script><?php endif; ?>
<?php if(!empty($companyNoticeDraftAssets)): ?><script type="module" src="/assets/js/company-notice-draft-discard.js"></script><?php endif; ?>
</body>
</html>
