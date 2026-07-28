<?php
declare(strict_types=1);
$toolSectionCards=isset($toolSectionCards)&&is_array($toolSectionCards)?$toolSectionCards:[];
if ($toolSectionCards !== []):
$escape=static fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<section class="gateway-tool-section" id="<?= $escape($toolSectionId) ?>" aria-labelledby="<?= $escape($toolSectionId) ?>-title">
 <div class="gateway-tool-section__header">
  <div><h2 id="<?= $escape($toolSectionId) ?>-title"><?= $escape($toolSectionTitle) ?></h2><p><?= $escape($toolSectionDescription) ?></p></div>
  <?php if (!empty($toolSectionScope)): ?><span class="gateway-tool-section__scope"><?= $escape($toolSectionScope) ?></span><?php endif; ?>
 </div>
 <div class="gateway-tool-grid">
  <?php foreach ($toolSectionCards as $toolCard): ?><?php require __DIR__.'/tool-card.php'; ?><?php endforeach; ?>
 </div>
</section>
<?php endif; ?>
