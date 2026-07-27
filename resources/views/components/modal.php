<?php

declare(strict_types=1);

$modalId = isset($modalId) ? (string) $modalId : 'gateway-modal';
$modalTitle = isset($modalTitle) ? (string) $modalTitle : '';
$modalBodyHtml = isset($modalBodyHtml) ? (string) $modalBodyHtml : '';
$modalFooterHtml = isset($modalFooterHtml) ? (string) $modalFooterHtml : '';
?>
<div class="modal fade gateway-modal" id="<?= htmlspecialchars($modalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
     tabindex="-1" aria-labelledby="<?= htmlspecialchars($modalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>-title"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gateway-modal__content">
            <header class="modal-header gateway-modal__header">
                <h2 class="modal-title gateway-modal__title"
                    id="<?= htmlspecialchars($modalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>-title">
                    <?= htmlspecialchars($modalTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </header>
            <div class="modal-body gateway-modal__body"><?= $modalBodyHtml ?></div>
            <?php if ($modalFooterHtml !== ''): ?>
                <footer class="modal-footer gateway-modal__footer"><?= $modalFooterHtml ?></footer>
            <?php endif; ?>
        </div>
    </div>
</div>
