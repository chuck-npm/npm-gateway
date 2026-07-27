<?php

declare(strict_types=1);

$alertType = isset($alertType) && in_array($alertType, ['info', 'success', 'warning', 'danger', 'error'], true)
    ? (string) $alertType
    : 'info';
$alertTitle = isset($alertTitle) ? (string) $alertTitle : '';
$alertMessage = isset($alertMessage) ? (string) $alertMessage : '';
$dismissible = isset($dismissible) && $dismissible === true;
if($alertMessage==='')return;
$alertType=$alertType==='error'?'danger':$alertType;
?>
<div class="alert gateway-alert gateway-alert--<?= htmlspecialchars($alertType, ENT_QUOTES, 'UTF-8') ?><?= $dismissible ? ' alert-dismissible fade show' : '' ?>"
     role="alert">
    <?php if ($alertTitle !== ''): ?>
        <strong class="gateway-alert__title"><?= htmlspecialchars($alertTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
    <?php endif; ?>
    <span><?= htmlspecialchars($alertMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
    <?php if ($dismissible): ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <?php endif; ?>
</div>
