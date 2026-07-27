<?php

declare(strict_types=1);

$emptyTitle = isset($emptyTitle) ? (string) $emptyTitle : 'Nothing to display';
$emptyMessage = isset($emptyMessage) ? (string) $emptyMessage : '';
$emptyIconHtml = isset($emptyIconHtml) ? (string) $emptyIconHtml : '';
$emptyActionHtml = isset($emptyActionHtml) ? (string) $emptyActionHtml : '';
?>
<section class="gateway-empty-state">
    <?php if ($emptyIconHtml !== ''): ?>
        <div class="gateway-empty-state__icon" aria-hidden="true"><?= $emptyIconHtml ?></div>
    <?php endif; ?>
    <h2 class="gateway-empty-state__title"><?= htmlspecialchars($emptyTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
    <?php if ($emptyMessage !== ''): ?>
        <p class="gateway-empty-state__message"><?= htmlspecialchars($emptyMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($emptyActionHtml !== ''): ?>
        <div class="gateway-empty-state__action"><?= $emptyActionHtml ?></div>
    <?php endif; ?>
</section>
