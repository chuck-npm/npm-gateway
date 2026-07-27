<?php

declare(strict_types=1);

$componentDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'components';
$contentHtml = isset($contentHtml) ? (string) $contentHtml : '';
$applicationName = isset($applicationName) ? (string) $applicationName : 'NPM Gateway';
$pageTitle = isset($pageTitle) ? (string) $pageTitle : $applicationName;
$navbarItems = isset($navbarItems) && is_array($navbarItems) ? $navbarItems : [];
$navbarUserLabel = isset($navbarUserLabel) ? (string) $navbarUserLabel : 'User menu';
$footerText = isset($footerText) ? (string) $footerText : 'NPM Gateway — Internal use only';

require $componentDirectory . DIRECTORY_SEPARATOR . 'header.php';
require $componentDirectory . DIRECTORY_SEPARATOR . 'navbar.php';
?>
<main class="gateway-main">
    <div class="container">
        <?= $contentHtml ?>
    </div>
</main>
<?php require $componentDirectory . DIRECTORY_SEPARATOR . 'footer.php'; ?>
