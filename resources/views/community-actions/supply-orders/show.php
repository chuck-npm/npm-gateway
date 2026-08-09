<?php
declare(strict_types=1);

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$components = dirname(__DIR__, 2).'/components';
ob_start();
$breadcrumbItems = [
    ['label' => 'Dashboard', 'url' => '/dashboard'],
    ['label' => 'Community Actions', 'url' => '/community-actions'],
    ['label' => $context->propertyDisplayName, 'url' => '/community-actions/'.$context->propertySlug],
    ['label' => 'Supply Orders', 'url' => '/community-actions/'.$context->propertySlug.'/supply-orders'],
    ['label' => 'Supply Order', 'current' => true],
];
require $components.'/breadcrumb.php';
$heading = 'Supply Order';
$description = 'Read-only submitted supply request for '.$context->propertyDisplayName.'.';
$eyebrow = 'Community Actions';
$actionsHtml = '';
require $components.'/page-header.php';

if ($success !== '') { ?><div class="alert gateway-alert gateway-alert--success" role="status"><?= $e($success) ?></div><?php }
if ($warning !== '') { ?><div class="alert gateway-alert gateway-alert--warning" role="alert"><?= $e($warning) ?></div><?php }
?>
<section class="card gateway-detail-card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-4">Property</dt><dd class="col-sm-8"><?= $e($order['property_name']) ?></dd>
            <dt class="col-sm-4">Submitted By</dt><dd class="col-sm-8"><?= $e($order['submitted_by_name']) ?></dd>
            <dt class="col-sm-4">Submitted At</dt><dd class="col-sm-8"><?= $e(\NpmGateway\Support\GatewayDateTimeFormatter::format($order['submitted_at'])) ?></dd>
        </dl>
        <h2>Supplies Requested</h2>
        <div class="gateway-rich-content"><?= $order['request_html'] ?></div>
    </div>
</section>
<div class="mt-4"><a class="btn btn-outline-secondary" href="/community-actions/<?= $e($context->propertySlug) ?>/supply-orders">Back to Supply Orders</a></div>
<?php
$contentHtml = (string) ob_get_clean();
$pageTitle = 'Supply Order — NPM Gateway';
$navbarItems = \NpmGateway\Support\Navigation::forRoute('/community-actions', dirname(__DIR__, 4));
$navbarUserLabel = $user->displayName;
$navbarUserContext = '@'.$user->username;
require dirname(__DIR__, 2).'/layouts/app.php';
