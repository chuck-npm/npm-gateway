<?php

declare(strict_types=1);

$cardTitle = isset($cardTitle) ? (string) $cardTitle : '';
$cardSubtitle = isset($cardSubtitle) ? (string) $cardSubtitle : '';
$cardBodyHtml = isset($cardBodyHtml) ? (string) $cardBodyHtml : '';
$cardFooterHtml = isset($cardFooterHtml) ? (string) $cardFooterHtml : '';
$cardVariant = isset($cardVariant) && in_array($cardVariant, ['standard', 'navigation', 'compact'], true)
    ? (string) $cardVariant
    : 'standard';
?>
<section class="gateway-card gateway-card--<?= htmlspecialchars($cardVariant, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($cardTitle !== '' || $cardSubtitle !== ''): ?>
        <header class="gateway-card__header">
            <?php if ($cardTitle !== ''): ?>
                <h2 class="gateway-card__title"><?= htmlspecialchars($cardTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
            <?php endif; ?>
            <?php if ($cardSubtitle !== ''): ?>
                <p class="gateway-card__subtitle"><?= htmlspecialchars($cardSubtitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    <div class="gateway-card__body"><?= $cardBodyHtml ?></div>
    <?php if ($cardFooterHtml !== ''): ?>
        <footer class="gateway-card__footer"><?= $cardFooterHtml ?></footer>
    <?php endif; ?>
</section>
