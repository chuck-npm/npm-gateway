<?php
declare(strict_types=1);
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$components=dirname(__DIR__,2).'/components';
ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Community Actions','url'=>'/community-actions'],['label'=>$context->propertyDisplayName,'url'=>'/community-actions/'.$context->propertySlug],['label'=>'Application Reviews','url'=>'/community-actions/'.$context->propertySlug.'/application-reviews'],['label'=>'Submit Prospect','current'=>true]];
require $components.'/breadcrumb.php';
$heading='Submit Prospect';
$description=$context->propertyDisplayName.' — Submit a prospect for Corporate review.';
$eyebrow='Application Reviews';
$actionsHtml='';
require $components.'/page-header.php';
?>
<form method="post" action="/community-actions/<?=$e($context->propertySlug)?>/application-reviews" data-processing-form data-processing-message="Submitting prospect for Corporate review…">
 <input type="hidden" name="_token" value="<?=$e($csrfToken)?>">
 <div class="card gateway-form-card">
  <div class="card-body">
   <section class="gateway-form-section gateway-prospect-information" aria-labelledby="prospect-information-heading">
    <h2 class="gateway-form-section-title" id="prospect-information-heading">Prospect Information</h2>
    <div class="mb-3 gateway-prospect-field">
     <label class="form-label" for="prospect_name">Prospect Full Name</label>
     <input class="form-control<?=$errors['prospect_name']??false?' is-invalid':''?>" id="prospect_name" name="prospect_name" maxlength="200" required value="<?=$e($input['prospect_name']??'')?>">
     <?php if(isset($errors['prospect_name'])):?><div class="invalid-feedback"><?=$e($errors['prospect_name'])?></div><?php endif;?>
    </div>
    <div class="mb-3 gateway-prospect-field">
     <label class="form-label" for="manager_comments">Comments for Reviewer (Optional)</label>
     <textarea class="form-control<?=$errors['manager_comments']??false?' is-invalid':''?>" id="manager_comments" name="manager_comments" maxlength="5000" rows="6" aria-describedby="manager-comments-counter<?=$errors['manager_comments']??false?' manager-comments-error':''?>" data-character-counter-target="manager-comments-counter" data-character-counter-max="5000"><?=$e($input['manager_comments']??'')?></textarea>
     <?php if(isset($errors['manager_comments'])):?><div class="invalid-feedback" id="manager-comments-error"><?=$e($errors['manager_comments'])?></div><?php endif;?>
     <div class="form-text gateway-character-counter" id="manager-comments-counter" data-character-counter aria-live="off"><?=mb_strlen((string)($input['manager_comments']??''))?> / 5000</div>
    </div>
   </section>
   <section class="gateway-form-section gateway-company-policy" aria-labelledby="required-company-policy-heading">
    <h2 class="gateway-form-section-title" id="required-company-policy-heading">Required Company Policy</h2>
    <h3 class="gateway-form-subsection-title">Rent Manager Confirmation</h3>
    <div class="alert gateway-alert gateway-alert--info gateway-company-policy__notice" role="note">Applications and supporting documents must be uploaded to Rent Manager before they can be submitted for Corporate review.</div>
    <h3 class="gateway-required-confirmation-title">Required Confirmation</h3>
    <div class="form-check mb-4 gateway-form-check--top-aligned">
     <input class="form-check-input<?=$errors['rm_documents_confirmed']??false?' is-invalid':''?>" type="checkbox" id="rm_documents_confirmed" name="rm_documents_confirmed" value="confirmed" required<?=($input['rm_documents_confirmed']??'')==='confirmed'?' checked':''?><?=isset($errors['rm_documents_confirmed'])?' aria-describedby="rm-documents-confirmed-error"':''?>>
     <label class="form-check-label" for="rm_documents_confirmed">I confirm that the application and all supporting documents have been uploaded to Rent Manager.</label>
     <?php if(isset($errors['rm_documents_confirmed'])):?><div class="invalid-feedback" id="rm-documents-confirmed-error"><?=$e($errors['rm_documents_confirmed'])?></div><?php endif;?>
    </div>
   </section>
   <div class="gateway-form-actions gateway-form-actions--primary-first" role="group" aria-label="Application Review form actions">
    <button class="btn gateway-button gateway-button--primary" type="submit">Submit Prospect</button>
    <a class="btn gateway-button gateway-button--secondary" href="/community-actions/<?=$e($context->propertySlug)?>/application-reviews">Cancel</a>
   </div>
  </div>
 </div>
</form>
<?php
$contentHtml=(string)ob_get_clean();
$pageTitle='Submit Prospect — '.$context->propertyDisplayName.' — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/community-actions',dirname(__DIR__,4));
$navbarUserLabel=$user->displayName;
$navbarUserContext='@'.$user->username;
require dirname(__DIR__,2).'/layouts/app.php';
