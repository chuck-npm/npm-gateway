<?php
declare(strict_types=1);
use NpmGateway\Services\RmAuditStatus;
$e=static fn(mixed$value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$components=dirname(__DIR__,2).'/components';ob_start();
$heading='RM Audit';$description='Review this Rent Manager tenant-file audit.';$eyebrow='RM Audit';$statusLabel=RmAuditStatus::LABELS[$audit['status']];$statusType=RmAuditStatus::BADGES[$audit['status']];ob_start();require$components.'/status-badge.php';$statusHtml=(string)ob_get_clean();$actionsHtml='<a class="btn gateway-button gateway-button--secondary" href="/corporate/rm-audits">Back to RM Audits</a>';require$components.'/page-header.php';
foreach(['success'=>'success','warning'=>'warning','error'=>'danger']as$value=>$tone):
 if($$value!==''):?><div class="alert gateway-alert gateway-alert--<?=$tone?>" role="status"><?=$e($$value)?></div><?php endif;
endforeach;
require$components.'/rm-audit-detail.php';
if($audit['status']==='completed'):?><section class="card mt-4"><div class="card-body"><h2>Return Audit</h2><form method="post" action="/corporate/rm-audits/<?=$e($audit['public_id'])?>/return" data-processing-form><input type="hidden" name="_token" value="<?=$e($csrfToken)?>"><label class="form-label" for="return_comments">Return Comments</label><p class="form-text">Explain what remains missing or incomplete.</p><textarea class="form-control" id="return_comments" name="return_comments" required minlength="10" maxlength="2000" rows="6"><?=$e($input['return_comments']??'')?></textarea><button class="btn btn-warning mt-3">Return Audit</button></form></div></section><?php endif;
$contentHtml=(string)ob_get_clean();$pageTitle='RM Audit — NPM Gateway';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/corporate/rm-audits',dirname(__DIR__,4));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,2).'/layouts/app.php';
