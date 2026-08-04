<?php

declare(strict_types=1);

$componentDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'components';
$contentHtml = isset($contentHtml) ? (string) $contentHtml : '';
$applicationName = isset($applicationName) ? (string) $applicationName : 'NPM Gateway';
$pageTitle = isset($pageTitle) ? (string) $pageTitle : $applicationName;
$navbarItems = isset($navbarItems) && is_array($navbarItems) ? $navbarItems : [];
$navbarCorporateItems = isset($navbarCorporateItems) && is_array($navbarCorporateItems) ? $navbarCorporateItems : [];
$navbarUserLabel = isset($navbarUserLabel) ? (string) $navbarUserLabel : 'User menu';
$navbarUserContext = isset($navbarUserContext) ? (string) $navbarUserContext : '';
$logoutCsrfToken = isset($logoutCsrfToken) ? (string) $logoutCsrfToken : '';
$footerText = isset($footerText) ? (string) $footerText : 'NPM Gateway — Internal use only';

require $componentDirectory . DIRECTORY_SEPARATOR . 'header.php';
?><a class="gateway-skip-link" href="#main-content">Skip to main content</a>
<header class="gateway-app-header"><?php require $componentDirectory . DIRECTORY_SEPARATOR . 'navbar.php'; ?></header><?php
?>
<main class="gateway-main" id="main-content" tabindex="-1">
    <div class="container">
        <?= $contentHtml ?>
    </div>
</main>
<?php require $componentDirectory . DIRECTORY_SEPARATOR . 'processing-overlay.php'; ?>
<?php require $componentDirectory . DIRECTORY_SEPARATOR . 'footer.php'; ?>
