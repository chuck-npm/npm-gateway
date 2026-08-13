<?php
declare(strict_types=1);
$components=dirname(__DIR__,3).'/components';
$e=static fn($value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$properties=isset($properties)&&is_array($properties)?$properties:[];
$months=isset($months)&&is_array($months)?$months:[];
$month=isset($month)?(string)$month:'';
$property=isset($property)?(string)$property:'';
$type=isset($type)&&in_array($type,['standard','promo'],true)?(string)$type:'standard';
$error=isset($error)?(string)$error:'';
ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Corporate','url'=>'/corporate'],['label'=>'Marketing','url'=>'/corporate/marketing'],['label'=>'Flyers','url'=>'/corporate/marketing/flyers'],['label'=>'New','current'=>true]];
require $components.'/breadcrumb.php';
$heading='New Flyer';$description='Upload a monthly or promotional flyer for an NPM community.';$eyebrow='Marketing';$actionsHtml='';
require $components.'/page-header.php';
if($error!==''):?><div class="alert gateway-alert gateway-alert--danger" role="alert"><?= $e($error) ?></div><?php endif; ?>
<form id="flyer-upload" class="gateway-card gateway-property-form-card gateway-flyer-form" method="post" enctype="multipart/form-data" action="/corporate/marketing/flyers">
 <div class="gateway-card__body">
  <input type="hidden" name="_token" value="<?= $e($csrfToken) ?>">
  <div class="row gateway-form-grid">
   <div class="col-12 col-lg-4"><label class="form-label" for="flyer-property">Property</label><select class="form-select" id="flyer-property" name="property" required><?php foreach($properties as$p):?><option value="<?= $e($p['public_id']) ?>"<?= $p['public_id']===$property?' selected':'' ?>><?= $e($p['display_name']) ?> (<?= $e($p['property_code']) ?>)</option><?php endforeach?></select></div>
   <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="flyer-month">Flyer Month</label><select class="form-select" id="flyer-month" name="month" required><?php foreach($months as$k=>$label):?><option value="<?= $e($k) ?>"<?= $k===$month?' selected':'' ?>><?= $e($label) ?></option><?php endforeach?></select></div>
   <div class="col-12 col-sm-6 col-lg-2"><label class="form-label" for="flyer-type">Flyer Type</label><select class="form-select" id="flyer-type" name="type" required><option value="standard"<?= $type==='standard'?' selected':'' ?>>Standard</option><option value="promo"<?= $type==='promo'?' selected':'' ?>>Promo</option></select></div>
   <div class="col-12 col-lg-3"><label class="form-label" for="flyer-file">File</label><input class="form-control" id="flyer-file" type="file" name="flyer" accept="image/png,image/jpeg" aria-describedby="flyer-file-help" required><div class="form-text" id="flyer-file-help">PNG or JPG, portrait orientation, up to 20 MB.</div></div>
  </div>
  <div id="flyer-progress" class="gateway-flyer-progress" hidden><div id="flyer-progress-status" class="gateway-flyer-progress__status" aria-live="polite">Uploading flyer... 0%</div><div class="gateway-flyer-progress__track" role="progressbar" aria-labelledby="flyer-progress-status" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="gateway-flyer-progress__bar"></div></div></div>
  <div class="gateway-form-actions gateway-form-actions--primary-first"><button class="btn gateway-button gateway-button--primary" type="submit">Upload Flyer</button><a class="btn gateway-button gateway-button--secondary" href="/corporate/marketing/flyers">Cancel</a></div>
 </div>
</form>
<?php $contentHtml=(string)ob_get_clean();$pageTitle='New Flyer — NPM Gateway';$flyerUploadAssets=true;$navbarItems=\NpmGateway\Support\Navigation::forRoute('/corporate/marketing',dirname(__DIR__,5));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,3).'/layouts/app.php';
