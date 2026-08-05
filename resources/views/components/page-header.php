<?php

declare(strict_types=1);

$heading = isset($heading) ? (string) $heading : '';
$description = isset($description) ? (string) $description : '';
$eyebrow = isset($eyebrow) ? (string) $eyebrow : '';
$actionsHtml = isset($actionsHtml) ? (string) $actionsHtml : '';
$statusHtml = isset($statusHtml) ? (string) $statusHtml : '';
$pageHeaderSpacious = isset($pageHeaderSpacious) && $pageHeaderSpacious === true;
?>
<header class="gateway-page-header<?= $pageHeaderSpacious?' gateway-page-header--spacious':'' ?>">
    <div class="gateway-page-header__content">
        <?php if ($eyebrow !== ''): ?>
            <span class="gateway-eyebrow"><?= htmlspecialchars($eyebrow, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        <?php endif; ?>
        <h1 class="gateway-page-header__title"><?= htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
        <?php if ($statusHtml !== ''): ?>
            <div class="gateway-page-header__status"><?= $statusHtml ?></div>
        <?php endif; ?>
        <?php if ($description !== ''): ?>
            <p class="gateway-page-header__description"><?= htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php if ($actionsHtml !== ''): ?>
        <div class="gateway-page-header__actions"><?= $actionsHtml ?></div>
    <?php endif; ?>
</header>
