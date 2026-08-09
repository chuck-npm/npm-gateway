<?php

declare(strict_types=1);

$applicationName = isset($applicationName) ? (string) $applicationName : 'NPM Gateway';
$pageTitle = isset($pageTitle) ? (string) $pageTitle : $applicationName;
$gatewayCssPath=dirname(__DIR__,3).'/public/assets/css/'.'gateway.css';
$gatewayCssVersion=is_file($gatewayCssPath)?(string)filemtime($gatewayCssPath):'1';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <?php if(!empty($quillAssets)||!empty($rmAuditAssets)||!empty($supplyOrderAssets)): ?><link href="/assets/vendor/quill/2.0.3/quill.snow.css" rel="stylesheet"><?php endif; ?>
    <link href="/assets/css/gateway.css?v=<?= rawurlencode($gatewayCssVersion) ?>" rel="stylesheet">
</head>
<body class="gateway-shell">
