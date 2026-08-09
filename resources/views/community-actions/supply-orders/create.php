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
    ['label' => 'Order Supplies', 'current' => true],
];
require $components.'/breadcrumb.php';
$heading = 'Order Supplies';
$description = 'Request supplies for '.$context->propertyDisplayName.'.';
$eyebrow = 'Community Actions';
$actionsHtml = '';
require $components.'/page-header.php';

if ($errors) {
    ?>
    <div class="alert gateway-alert gateway-alert--danger" role="alert">
        <p>Please correct the following:</p>
        <ul><?php foreach ($errors as $field => $message) { ?><li><a href="#<?= $e($field) ?>"><?= $e($message) ?></a></li><?php } ?></ul>
    </div>
    <?php
}
?>
<form class="gateway-card gateway-property-form-card gateway-supply-order-form" method="post" action="/community-actions/<?= $e($context->propertySlug) ?>/supply-orders" data-processing-form data-supply-order-editor>
    <input type="hidden" name="_token" value="<?= $e($csrfToken) ?>">
    <div class="gateway-card__body">
        <label class="form-label" for="request_html">Supplies Requested</label>
        <p id="supply-request-help" class="form-text">List the supplies needed for this community. Include quantities, sizes, model numbers, or product links when helpful.</p>
        <textarea class="form-control gateway-supply-order-form__fallback" required rows="12" id="request_html" name="request_html" aria-describedby="supply-request-help"><?= $e($input['request_html'] ?? '') ?></textarea>
        <div class="gateway-supply-order-form__editor" id="supply-order-editor" hidden></div>
        <div class="invalid-feedback d-block" id="request_html_error" data-supply-order-error<?= isset($errors['request_html']) ? '' : ' hidden' ?>><?= $e($errors['request_html'] ?? '') ?></div>
        <div class="gateway-form-actions gateway-form-actions--primary-first">
            <button class="btn gateway-button gateway-button--primary" type="submit">Submit Supply Order</button>
            <a class="btn gateway-button gateway-button--secondary" href="/community-actions/<?= $e($context->propertySlug) ?>/supply-orders">Cancel</a>
        </div>
    </div>
</form>
<?php
$contentHtml = (string) ob_get_clean();
$pageTitle = 'Order Supplies — NPM Gateway';
$supplyOrderAssets = true;
$navbarItems = \NpmGateway\Support\Navigation::forRoute('/community-actions', dirname(__DIR__, 4));
$navbarUserLabel = $user->displayName;
$navbarUserContext = '@'.$user->username;
require dirname(__DIR__, 2).'/layouts/app.php';
