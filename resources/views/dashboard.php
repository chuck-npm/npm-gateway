<?php
declare(strict_types=1);
$components=__DIR__.'/components';
$escape=static fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$summary=$home->setupSummary;
ob_start();
?>
<section class="gateway-welcome-banner" aria-labelledby="gateway-welcome-title">
 <p class="gateway-welcome-eyebrow">NPM Gateway · <?= $escape($home->employeeClassLabel) ?></p>
 <h1 id="gateway-welcome-title">Welcome, <?= $escape($home->welcomeName) ?>.</h1>
 <p>What would you like to do today?</p>
 <?php if ($home->jobTitle !== ''): ?><span class="gateway-welcome-role"><?= $escape($home->jobTitle) ?></span><?php endif; ?>
</section>
<?php
$toolSectionTitle='Universal Tools';$toolSectionDescription='Functions available to every Gateway user.';$toolSectionId='universal-tools';$toolSectionScope='12 tools';$toolSectionCards=$home->universalTools;
require $components.'/tool-section.php';
$toolSectionTitle='Corporate Tools';$toolSectionDescription='Corporate categories are visible to every Gateway user; access is assigned separately.';$toolSectionId='corporate-tools';$toolSectionScope='5 categories';$toolSectionCards=$home->corporateTools;
require $components.'/tool-section.php';
?>
<section class="gateway-setup-panel" aria-labelledby="gateway-setup-title">
 <div>
  <p class="gateway-setup-panel__eyebrow">System status</p>
  <h2 id="gateway-setup-title"><?= $summary->initialSetup?'Gateway Setup':'Gateway foundation ready' ?></h2>
  <?php if ($summary->initialSetup): ?><p>Administrator account created · Secure login enabled</p><p class="mb-0"><strong>Next:</strong> Add the first property when Property Management becomes available.</p>
  <?php else: ?><p class="mb-0">Configured records are ready for operational workflows.</p><?php endif; ?>
 </div>
 <dl class="gateway-system-summary" aria-label="Current Gateway record totals">
  <div><dt>Properties</dt><dd><?= $summary->propertyCount ?></dd></div>
  <div><dt>Employees</dt><dd><?= $summary->employeeCount ?></dd></div>
  <div><dt>Users</dt><dd><?= $summary->userCount ?></dd></div>
  <div><dt>Assignments</dt><dd><?= $summary->activeAssignmentCount ?></dd></div>
 </dl>
</section>
<?php
$contentHtml=(string)ob_get_clean();
$pageTitle='Dashboard — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/dashboard',dirname(__DIR__,2));
$navbarCorporateItems=$home->corporateTools;
$navbarUserLabel=$home->welcomeName;
$navbarUserContext='@'.$user->username.($home->jobTitle!==''?' · '.$home->jobTitle:'');
$logoutCsrfToken=$csrfToken;
$footerText='NPM Gateway — Internal use only';
require __DIR__.'/layouts/app.php';
