<?php
declare(strict_types=1);
$statusLabel=isset($statusLabel)?(string)$statusLabel:'';
$statusType=isset($statusType)&&in_array($statusType,['success','warning','neutral'],true)?(string)$statusType:'neutral';
?>
<span class="gateway-status gateway-status--<?= htmlspecialchars($statusType,ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars($statusLabel,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span>
