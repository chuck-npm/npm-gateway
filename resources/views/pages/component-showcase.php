<?php

declare(strict_types=1);

$pageTitle = 'Component Showcase — Development Only';
$navbarItems = [
    ['label' => 'Showcase', 'url' => '/component-showcase', 'active' => true],
    ['label' => 'Directory', 'url' => '#directory', 'active' => false],
    ['label' => 'Resources', 'url' => '#resources', 'active' => false],
];
$navbarUserLabel = 'User menu';

$componentDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'components';
$renderComponent = static function (string $component, array $variables = []) use ($componentDirectory): string {
    extract($variables, EXTR_SKIP);
    ob_start();
    require $componentDirectory . DIRECTORY_SEPARATOR . $component . '.php';

    return (string) ob_get_clean();
};
?>
<div class="gateway-development-banner" role="status">
    <strong>Component Showcase — Development Only</strong>
    <span>This temporary page previews the Gateway presentation system.</span>
</div>

<?= $renderComponent('breadcrumb', [
    'breadcrumbItems' => [
        ['label' => 'Gateway', 'url' => '/'],
        ['label' => 'Component Showcase', 'current' => true],
    ],
]) ?>

<?= $renderComponent('page-header', [
    'eyebrow' => 'Design system',
    'heading' => 'Gateway component showcase',
    'description' => 'Reusable interface patterns for a clear, consistent internal administrative portal.',
]) ?>

<div class="row gateway-showcase-layout">
    <div class="col-12">
        <section class="gateway-showcase-section" id="overview">
            <h2 class="gateway-showcase-section__title">Page headers</h2>
            <div class="gateway-showcase-stack">
                <?= $renderComponent('page-header', [
                    'heading' => 'Company resources',
                    'description' => 'Find policies, forms, and shared operational references.',
                ]) ?>
                <?= $renderComponent('page-header', [
                    'heading' => 'Community directory',
                    'description' => 'Review example community records and administrative actions.',
                    'actionsHtml' => '<button class="btn gateway-button gateway-button--secondary" type="button">Export list</button>'
                        . '<button class="btn gateway-button gateway-button--primary" type="button">Add record</button>',
                ]) ?>
            </div>
        </section>

        <section class="gateway-showcase-section" id="alerts">
            <h2 class="gateway-showcase-section__title">Alerts</h2>
            <div class="gateway-showcase-stack">
                <?= $renderComponent('alert', [
                    'alertType' => 'success',
                    'alertTitle' => 'Complete.',
                    'alertMessage' => 'The sample record was saved successfully.',
                ]) ?>
                <?= $renderComponent('alert', [
                    'alertType' => 'info',
                    'alertTitle' => 'For your information.',
                    'alertMessage' => 'A planned maintenance window is scheduled for this example.',
                ]) ?>
                <?= $renderComponent('alert', [
                    'alertType' => 'warning',
                    'alertTitle' => 'Review needed.',
                    'alertMessage' => 'This placeholder item requires attention before publication.',
                ]) ?>
                <?= $renderComponent('alert', [
                    'alertType' => 'danger',
                    'alertTitle' => 'Unable to continue.',
                    'alertMessage' => 'The example request could not be completed.',
                    'dismissible' => true,
                ]) ?>
            </div>
        </section>

        <section class="gateway-showcase-section" id="cards">
            <h2 class="gateway-showcase-section__title">Cards</h2>
            <div class="row gateway-showcase-grid">
                <div class="col-12">
                    <?= $renderComponent('card', [
                        'cardTitle' => 'Standard content card',
                        'cardSubtitle' => 'A general-purpose panel for administrative content.',
                        'cardBodyHtml' => '<p class="gateway-content-copy">This static example shows a reusable content surface with a shared header, body, and footer.</p>',
                        'cardFooterHtml' => '<button class="btn gateway-button gateway-button--primary" type="button">Review example</button>',
                    ]) ?>
                </div>
                <div class="col-12 col-md-7">
                    <?= $renderComponent('card', [
                        'cardVariant' => 'navigation',
                        'cardTitle' => 'Dashboard navigation',
                        'cardSubtitle' => 'Common destinations',
                        'cardBodyHtml' => '<nav class="gateway-card-links" aria-label="Example dashboard destinations">'
                            . '<a href="#resources">Company resources</a>'
                            . '<a href="#navigation">Navigation patterns</a>'
                            . '<a href="#empty-states">Empty-state patterns</a>'
                            . '</nav>',
                    ]) ?>
                </div>
                <div class="col-12 col-md-5">
                    <?= $renderComponent('card', [
                        'cardVariant' => 'compact',
                        'cardTitle' => 'Open examples',
                        'cardBodyHtml' => '<p class="gateway-summary-value">12</p><p class="gateway-summary-label">Placeholder items awaiting review</p>',
                    ]) ?>
                </div>
            </div>
        </section>

        <section class="gateway-showcase-section" id="empty-states">
            <h2 class="gateway-showcase-section__title">Empty states</h2>
            <div class="row gateway-showcase-grid">
                <div class="col-12 col-md-6">
                    <?= $renderComponent('empty-state', [
                        'emptyTitle' => 'No records yet',
                        'emptyMessage' => 'Records will appear here after sample content is added.',
                        'emptyIconHtml' => '<span aria-hidden="true">＋</span>',
                        'emptyActionHtml' => '<button class="btn gateway-button gateway-button--primary" type="button">Add sample record</button>',
                    ]) ?>
                </div>
                <div class="col-12 col-md-6">
                    <?= $renderComponent('empty-state', [
                        'emptyTitle' => 'No search results',
                        'emptyMessage' => 'Try adjusting the example search terms or clearing filters.',
                        'emptyIconHtml' => '<span aria-hidden="true">⌕</span>',
                        'emptyActionHtml' => '<button class="btn gateway-button gateway-button--secondary" type="button">Clear filters</button>',
                    ]) ?>
                </div>
            </div>
        </section>

        <section class="gateway-showcase-section" id="navigation">
            <h2 class="gateway-showcase-section__title">Breadcrumbs and pagination</h2>
            <div class="gateway-showcase-stack">
                <?= $renderComponent('breadcrumb', [
                    'breadcrumbItems' => [
                        ['label' => 'Resources', 'url' => '#resources'],
                        ['label' => 'Policies', 'current' => true],
                    ],
                ]) ?>
                <?= $renderComponent('breadcrumb', [
                    'breadcrumbItems' => [
                        ['label' => 'Communities', 'url' => '#communities'],
                        ['label' => 'Example community', 'url' => '#example-community'],
                        ['label' => 'Documents', 'current' => true],
                    ],
                ]) ?>
                <div>
                    <p class="gateway-example-label">First page with previous disabled</p>
                    <?= $renderComponent('pagination', [
                        'currentPage' => 1,
                        'totalPages' => 5,
                        'pageUrlPattern' => '/component-showcase?page=%d#navigation',
                    ]) ?>
                </div>
                <div>
                    <p class="gateway-example-label">Current page with previous and next links</p>
                    <?= $renderComponent('pagination', [
                        'currentPage' => 3,
                        'totalPages' => 5,
                        'pageUrlPattern' => '/component-showcase?page=%d#navigation',
                    ]) ?>
                </div>
            </div>
        </section>

        <section class="gateway-showcase-section">
            <h2 class="gateway-showcase-section__title">Modal</h2>
            <p class="gateway-content-copy">The modal uses Bootstrap’s accessible interaction behavior and Gateway presentation styles.</p>
            <button class="btn gateway-button gateway-button--primary" type="button"
                    data-bs-toggle="modal" data-bs-target="#showcase-confirmation">
                Open example modal
            </button>
        </section>
    </div>
</div>

<?= $renderComponent('modal', [
    'modalId' => 'showcase-confirmation',
    'modalTitle' => 'Confirm example action',
    'modalBodyHtml' => '<p class="gateway-content-copy">This is static development-only content. No application action will be performed.</p>',
    'modalFooterHtml' => '<button class="btn gateway-button gateway-button--secondary" type="button" data-bs-dismiss="modal">Cancel</button>'
        . '<button class="btn gateway-button gateway-button--primary" type="button" data-bs-dismiss="modal">Confirm example</button>',
]) ?>
