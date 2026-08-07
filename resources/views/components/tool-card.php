<?php
declare(strict_types=1);
use NpmGateway\ValueObjects\ToolCard;
if (!isset($toolCard) || !$toolCard instanceof ToolCard) throw new InvalidArgumentException('A ToolCard is required.');
$escape=static fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$label=$toolCard->accessibilityLabel??($toolCard->enabled?'Open '.$toolCard->title:$toolCard->title.', not yet enabled');
?>
<?php if ($toolCard->enabled): ?>
<a class="gateway-tool-card gateway-tool-card--enabled" href="<?= $escape((string)$toolCard->route) ?>" aria-label="<?= $escape($label) ?>" data-tool-key="<?= $escape($toolCard->key) ?>">
<?php else: ?>
<article class="gateway-tool-card gateway-tool-card--disabled" aria-label="<?= $escape($label) ?>" data-tool-key="<?= $escape($toolCard->key) ?>">
<?php endif; ?>
 <div class="gateway-tool-card__body">
  <p class="gateway-tool-card__category"><?= $escape($toolCard->categoryLabel) ?></p>
  <div class="d-flex flex-wrap align-items-center gap-2"><h3 class="gateway-tool-card__title mb-0"><?= $escape($toolCard->title) ?></h3><?php if($toolCard->attentionCount!==null): ?><span class="gateway-status gateway-status--warning"><?= $escape((string)$toolCard->attentionCount.' '.(string)$toolCard->attentionLabel) ?></span><?php endif; ?></div>
  <p class="gateway-tool-card__description"><?= $escape($toolCard->description) ?></p>
 </div>
 <div class="gateway-tool-card__footer">
  <span><?= $escape($toolCard->footerLabel) ?></span>
  <?php if ($toolCard->badgeLabel !== null): ?><span class="gateway-tool-status"><?= $escape($toolCard->badgeLabel) ?></span><?php endif; ?>
 </div>
<?php if ($toolCard->enabled): ?></a><?php else: ?></article><?php endif; ?>
