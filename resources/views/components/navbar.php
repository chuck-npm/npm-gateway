<?php

declare(strict_types=1);

$navbarBrand = isset($navbarBrand) ? (string) $navbarBrand : 'NPM Gateway';
$navbarBrandUrl = isset($navbarBrandUrl) ? (string) $navbarBrandUrl : '/';
$navbarItems = isset($navbarItems) && is_array($navbarItems) ? $navbarItems : [];
$navbarCorporateItems = isset($navbarCorporateItems) && is_array($navbarCorporateItems) ? $navbarCorporateItems : [];
$showCorporateTools = isset($showCorporateTools) && $showCorporateTools === true;
$navbarUserLabel = isset($navbarUserLabel) ? (string) $navbarUserLabel : 'User menu';
$navbarUserContext = isset($navbarUserContext) ? (string) $navbarUserContext : '';
$logoutCsrfToken = isset($logoutCsrfToken) ? (string) $logoutCsrfToken : '';
?>
<nav class="navbar navbar-expand-lg gateway-navbar" aria-label="Primary navigation">
    <div class="container gateway-navbar__inner">
        <a class="navbar-brand gateway-navbar__brand"
           href="<?= htmlspecialchars($navbarBrandUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?= htmlspecialchars($navbarBrand, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </a>
        <button class="navbar-toggler gateway-navbar__toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#gateway-primary-navigation" aria-controls="gateway-primary-navigation"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse gateway-navbar__collapse" id="gateway-primary-navigation">
            <ul class="navbar-nav gateway-navbar__list">
                <?php foreach ($navbarItems as $item): ?>
                    <?php
                    $label = isset($item['label']) ? (string) $item['label'] : '';
                    $url = isset($item['url']) ? (string) $item['url'] : '#';
                    $active = isset($item['active']) && $item['active'] === true;
                    ?>
                    <li class="nav-item gateway-navbar__item">
                        <a class="nav-link gateway-navbar__link<?= $active ? ' active' : '' ?>"
                           href="<?= htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                           <?= $active ? 'aria-current="page"' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php if ($showCorporateTools && $navbarCorporateItems !== []): ?>
                <li class="nav-item dropdown gateway-navbar__item gateway-navbar__corporate">
                    <button class="nav-link dropdown-toggle gateway-navbar__link gateway-navbar__corporate-toggle"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="Corporate tools menu">
                        Corporate
                    </button>
                    <ul class="dropdown-menu gateway-navbar__menu" aria-label="Corporate tools">
                        <?php foreach ($navbarCorporateItems as $corporateItem): ?>
                        <?php if($corporateItem->enabled): ?><li><a class="dropdown-item" href="<?= htmlspecialchars((string)$corporateItem->route,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>"><?= htmlspecialchars($corporateItem->title,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></a></li><?php else: ?><li><span class="dropdown-item-text gateway-navbar__disabled-item"><?= htmlspecialchars($corporateItem->title,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?><span class="gateway-navbar__planned">Planned</span></span></li><?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown gateway-navbar__item gateway-navbar__user">
                    <button class="nav-link dropdown-toggle gateway-navbar__link gateway-navbar__user-toggle"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars($navbarUserLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end gateway-navbar__menu">
                        <?php if ($navbarUserContext !== ''): ?><li><span class="dropdown-item-text gateway-navbar__context"><?= htmlspecialchars($navbarUserContext,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></li><li><hr class="dropdown-divider"></li><?php endif; ?>
                        <?php if ($logoutCsrfToken !== ''): ?><li><form method="post" action="/logout"><input type="hidden" name="_token" value="<?= htmlspecialchars($logoutCsrfToken,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>"><button class="dropdown-item" type="submit">Sign out</button></form></li><?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
