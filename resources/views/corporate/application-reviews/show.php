<?php
declare(strict_types=1);
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$components=dirname(__DIR__,2).'/components';
ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Corporate','url'=>'/dashboard'],['label'=>'Application Reviews','url'=>'/corporate/application-reviews'],['label'=>'Review Detail','current'=>true]];
require $components.'/breadcrumb.php';
$heading='Application Reviews';
$description='Review prospect submissions from all communities.';
$eyebrow='Application Reviews';
$actionsHtml='';
$statusLabel=['pending_review'=>'Pending Review','approved'=>'Approved','denied'=>'Denied'][$review['status']];
$statusType=['pending_review'=>'neutral','approved'=>'success','denied'=>'danger'][$review['status']];
ob_start();require $components.'/status-badge.php';$statusHtml=(string)ob_get_clean();
require $components.'/page-header.php';
if($success!==''):?><div class="alert gateway-alert gateway-alert--success" role="status"><?=$e($success)?></div><?php endif;
if(($decisionError??'')!==''):?><div class="alert gateway-alert gateway-alert--danger" role="alert"><?=$e($decisionError)?></div><?php endif;
if($errors!==[]):?><div class="alert gateway-alert gateway-alert--danger" role="alert" aria-labelledby="decision-error-summary"><p id="decision-error-summary" class="mb-0">Please correct the following:</p><ul class="mb-0"><?php foreach($errors as $field=>$message):?><li><a href="#<?=$e($field)?>"><?=$e($message)?></a></li><?php endforeach;?></ul></div><?php endif;
require $components.'/application-review-detail.php';
if($review['status']==='pending_review'):?>
<section class="card gateway-form-card" aria-labelledby="decision-heading"><div class="card-body"><h2 id="decision-heading">Review Decision</h2><form method="post" action="/corporate/application-reviews/<?=$e($review['public_id'])?>/decision" data-processing-form data-processing-message="Saving decision…"><input type="hidden" name="_token" value="<?=$e($csrfToken)?>"><div class="mb-3"><label class="form-label" for="reviewer_comments">Review Notes</label><textarea class="form-control<?=$errors['reviewer_comments']??false?' is-invalid':''?>" id="reviewer_comments" name="reviewer_comments" maxlength="5000" rows="6"<?=$errors['reviewer_comments']??false?' aria-describedby="reviewer_comments_error" aria-invalid="true"':''?> required><?=$e($input['reviewer_comments']??'')?></textarea><?php if(isset($errors['reviewer_comments'])):?><div class="invalid-feedback" id="reviewer_comments_error"><?=$e($errors['reviewer_comments'])?></div><?php endif;?></div><div class="gateway-review-decision-actions"><button class="btn btn-success" type="submit" name="decision" value="approved">Approve</button><button class="btn btn-danger" type="submit" name="decision" value="denied">Deny</button><a class="btn btn-secondary gateway-review-decision-actions__cancel" href="/corporate/application-reviews">Cancel</a></div></form></div></section>
<?php endif;
$contentHtml=(string)ob_get_clean();
$pageTitle='Application Reviews — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/corporate/application-reviews',dirname(__DIR__,4));
$navbarUserLabel=$user->displayName;
$navbarUserContext='@'.$user->username;
require dirname(__DIR__,2).'/layouts/app.php';
