<?php
declare(strict_types=1);
$components=__DIR__.'/components';
ob_start();
$breadcrumbItems=[['label'=>'Dashboard','current'=>true]];
require $components.'/breadcrumb.php';
$heading='Dashboard';$description='A current view of the NPM Gateway foundation.';$eyebrow='Gateway overview';$actionsHtml='';
require $components.'/page-header.php';
?>
<section class="row g-4" aria-label="Gateway summary">
<?php foreach ([['Properties',$summary->propertyCount],['Employees',$summary->employeeCount],['Users',$summary->userCount],['Active users',$summary->activeUserCount],['Active assignments',$summary->activeAssignmentCount]] as [$label,$value]): ?>
 <div class="col-12 col-sm-6 col-xl"><?php $cardLabel=$label;$cardValue=(string)$value;$cardSupportingText='Verified current total';require $components.'/dashboard-card.php'; ?></div>
<?php endforeach; ?>
</section>
<section class="mt-4" aria-labelledby="gateway-state-title">
 <?php if ($summary->initialSetup): ?>
  <div class="gateway-card"><div class="gateway-card__body"><h2 class="gateway-card__title" id="gateway-state-title">Welcome to NPM Gateway</h2><p class="gateway-card__subtitle">Gateway is initialized and secure login is enabled.</p><h3 class="h6 mt-4">Confirmed</h3><ul><li>Administrator account created</li><li>Secure database-backed authentication enabled</li></ul><h3 class="h6 mt-4">Upcoming setup tasks</h3><ol><li>Add the first property</li><li>Add or import employees</li><li>Create additional Gateway users</li><li>Assign employees to properties</li></ol><p class="mb-0 text-secondary">Additional setup tools are not yet enabled.</p></div></div>
 <?php else: ?>
  <div class="gateway-card"><div class="gateway-card__body"><div class="d-flex align-items-center justify-content-between gap-3"><div><h2 class="gateway-card__title" id="gateway-state-title">Gateway foundation ready</h2><p class="gateway-card__subtitle">Configured records are available for future operational workflows.</p></div><?php $statusLabel='Ready';$statusType='success';require $components.'/status-badge.php'; ?></div></div></div>
 <?php endif; ?>
</section>
<?php
$contentHtml=(string)ob_get_clean();
$pageTitle='Dashboard — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/dashboard',dirname(__DIR__,2));
$navbarUserLabel=$summary->displayName;
$navbarUserContext='@'.$user->username.($summary->jobTitle!==''?' · '.$summary->jobTitle:'');
$logoutCsrfToken=$csrfToken;
$footerText='NPM Gateway — Internal use only';
require __DIR__.'/layouts/app.php';
