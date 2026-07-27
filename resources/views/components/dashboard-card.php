<?php
declare(strict_types=1);
$cardLabel=isset($cardLabel)?(string)$cardLabel:'';
$cardValue=isset($cardValue)?(string)$cardValue:'0';
$cardSupportingText=isset($cardSupportingText)?(string)$cardSupportingText:'';
?>
<article class="gateway-card gateway-card--compact h-100">
 <div class="gateway-card__body">
  <h2 class="gateway-summary-label"><?= htmlspecialchars($cardLabel,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></h2>
  <p class="gateway-summary-value"><?= htmlspecialchars($cardValue,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></p>
  <?php if($cardSupportingText!==''): ?><p class="gateway-card__subtitle"><?= htmlspecialchars($cardSupportingText,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></p><?php endif; ?>
 </div>
</article>
