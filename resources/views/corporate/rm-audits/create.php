<?php
declare(strict_types=1);
$e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$components=dirname(__DIR__,2).'/components';
ob_start();
$heading='New RM Audit';$description='Record missing or incomplete Rent Manager tenant-file items for manager follow-up.';$eyebrow='RM Audit';$actionsHtml='';require $components.'/page-header.php';
if($errors):?><div class="alert gateway-alert gateway-alert--danger" role="alert"><p>Please correct the following:</p><ul><?php foreach($errors as$field=>$message):?><li><a href="#<?=$e($field)?>"><?=$e($message)?></a></li><?php endforeach?></ul></div><?php endif?>
<form class="gateway-card gateway-property-form-card gateway-rm-audit-form" method="post" action="/corporate/rm-audits" data-processing-form data-rm-audit-editor>
 <input type="hidden" name="_token" value="<?=$e($csrfToken)?>">
 <div class="gateway-card__body">
  <div class="row gateway-form-grid gateway-rm-audit-form__identity-row">
   <div class="col-12 col-lg-4"><label class="form-label" for="property_public_id">Property</label><select class="form-select" required id="property_public_id" name="property_public_id"><option value="">Select a property</option><?php foreach($properties as$property):?><option value="<?=$e($property['public_id'])?>"<?=($input['property_public_id']??'')===$property['public_id']?' selected':''?>><?=$e($property['display_name'])?></option><?php endforeach?></select></div>
   <div class="col-12 col-lg-5"><label class="form-label" for="tenant_name">Tenant Name</label><input class="form-control" required maxlength="200" id="tenant_name" name="tenant_name" value="<?=$e($input['tenant_name']??'')?>"></div>
   <div class="col-12 col-lg-3"><label class="form-label" for="unit_identifier">Unit #</label><input class="form-control" required maxlength="100" id="unit_identifier" name="unit_identifier" value="<?=$e($input['unit_identifier']??'')?>"></div>
  </div>
  <div class="gateway-rm-audit-form__findings">
   <label class="form-label" for="audit_findings_html">Audit Findings</label>
   <p id="audit-findings-help" class="form-text">List the missing or incomplete items that must be corrected in Rent Manager.</p>
   <textarea class="form-control gateway-rm-audit-form__fallback" required rows="10" id="audit_findings_html" name="audit_findings_html" aria-describedby="audit-findings-help"><?=$e($input['audit_findings_html']??'')?></textarea>
   <div class="gateway-rm-audit-form__editor" id="rm-audit-editor" hidden></div>
   <div class="invalid-feedback d-block" id="audit_findings_html_error" data-rm-audit-findings-error<?=isset($errors['audit_findings_html'])?'':' hidden'?>><?=$e($errors['audit_findings_html']??'')?></div>
  </div>
  <div class="gateway-form-actions gateway-form-actions--primary-first" role="group" aria-label="RM Audit form actions"><button class="btn gateway-button gateway-button--primary" type="submit">Create RM Audit</button><a class="btn gateway-button gateway-button--secondary" href="/corporate/rm-audits">Cancel</a></div>
 </div>
</form>
<?php $contentHtml=(string)ob_get_clean();$pageTitle='New RM Audit — NPM Gateway';$rmAuditAssets=true;$navbarItems=\NpmGateway\Support\Navigation::forRoute('/corporate/rm-audits',dirname(__DIR__,4));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,2).'/layouts/app.php';
