<?php
declare(strict_types=1);

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$preview = new \NpmGateway\Support\SupplyOrderPreviewFormatter();
$components = dirname(__DIR__, 2).'/components';
ob_start();
$breadcrumbItems = [
    ['label' => 'Dashboard', 'url' => '/dashboard'],
    ['label' => 'Community Actions', 'url' => '/community-actions'],
    ['label' => $context->propertyDisplayName, 'url' => '/community-actions/'.$context->propertySlug],
    ['label' => 'Supply Orders', 'current' => true],
];
require $components.'/breadcrumb.php';
$heading = 'Supply Orders';
$description = 'Review supply orders submitted for '.$context->propertyDisplayName.'.';
$eyebrow = 'Community Actions';
$actionsHtml = '<a class="btn gateway-button gateway-button--primary" href="/community-actions/'.$e($context->propertySlug).'/supply-orders/create">Order Supplies</a>';
require $components.'/page-header.php';

if (!$orders) {
    $emptyTitle = 'No supply orders yet';
    $emptyMessage = 'Submitted supply orders for this community will appear here.';
    $emptyIconHtml = '';
    $emptyActionHtml = '';
    require $components.'/empty-state.php';
} else {
    ?>
    <div class="table-responsive">
        <table class="table gateway-directory-table">
            <thead><tr><th>Submitted</th><th>Ordered By</th><th>Supplies</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order) { ?>
                <tr>
                    <td data-label="Submitted"><?= $e(\NpmGateway\Support\GatewayDateTimeFormatter::format($order['submitted_at'])) ?></td>
                    <td data-label="Ordered By"><?= $e($order['submitted_by_name']) ?></td>
                    <td data-label="Supplies"><?= $e($preview->format($order['request_html'])) ?></td>
                    <td data-label="Action"><a href="/community-actions/<?= $e($context->propertySlug) ?>/supply-orders/<?= $e($order['public_id']) ?>">View</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}

$contentHtml = (string) ob_get_clean();
$pageTitle = 'Supply Orders — NPM Gateway';
$navbarItems = \NpmGateway\Support\Navigation::forRoute('/community-actions', dirname(__DIR__, 4));
$navbarUserLabel = $user->displayName;
$navbarUserContext = '@'.$user->username;
require dirname(__DIR__, 2).'/layouts/app.php';
