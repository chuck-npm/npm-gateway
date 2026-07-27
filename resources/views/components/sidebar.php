<?php

declare(strict_types=1);

// Retired: preserved for reference but no longer used by the standard Gateway layout.
$sidebarLabel = isset($sidebarLabel) ? (string) $sidebarLabel : 'Portal navigation';
$sidebarItems = isset($sidebarItems) && is_array($sidebarItems) ? $sidebarItems : [];
?>
<aside class="gateway-sidebar" aria-label="<?= htmlspecialchars($sidebarLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <nav class="gateway-sidebar__nav">
        <ul class="gateway-sidebar__list">
            <?php foreach ($sidebarItems as $item): ?>
                <?php
                $label = isset($item['label']) ? (string) $item['label'] : '';
                $url = isset($item['url']) ? (string) $item['url'] : '#';
                $active = isset($item['active']) && $item['active'] === true;
                ?>
                <li class="gateway-sidebar__item">
                    <a class="gateway-sidebar__link<?= $active ? ' gateway-sidebar__link--active' : '' ?>"
                       href="<?= htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                       <?= $active ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
