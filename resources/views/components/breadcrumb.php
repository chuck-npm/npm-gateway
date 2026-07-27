<?php

declare(strict_types=1);

$breadcrumbItems = isset($breadcrumbItems) && is_array($breadcrumbItems) ? $breadcrumbItems : [];
?>
<nav class="gateway-breadcrumb" aria-label="Breadcrumb">
    <ol class="breadcrumb gateway-breadcrumb__list">
        <?php foreach ($breadcrumbItems as $item): ?>
            <?php
            $label = isset($item['label']) ? (string) $item['label'] : '';
            $url = isset($item['url']) ? (string) $item['url'] : '';
            $isCurrent = isset($item['current']) && $item['current'] === true;
            ?>
            <li class="breadcrumb-item gateway-breadcrumb__item<?= $isCurrent ? ' active' : '' ?>"
                <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                <?php if (!$isCurrent && $url !== ''): ?>
                    <a href="<?= htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
