<?php
declare(strict_types=1);
use NpmGateway\Services\RmAuditStatus;
$e=static fn(mixed$value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$components=dirname(__DIR__,2).'/components';ob_start();
$heading='RM Audit';$description='Review and resolve this Rent Manager tenant-file audit.';$eyebrow=$context->propertyDisplayName;$statusLabel=RmAuditStatus::LABELS[$audit['status']];$statusType=RmAuditStatus::BADGES[$audit['status']];ob_start();require$components.'/status-badge.php';$statusHtml=(string)ob_get_clean();$actionsHtml='<a class="btn gateway-button gateway-button--secondary" href="/community-actions/'.$e($context->propertySlug).'/rm-audits">Back to RM Audits</a>';require$components.'/page-header.php';
foreach(['success'=>'success','warning'=>'warning','error'=>'danger']as$value=>$tone):
 if($$value!==''):?><div class="alert gateway-alert gateway-alert--<?=$tone?>" role="status"><?=$e($$value)?></div><?php endif;
endforeach;
require$components.'/rm-audit-detail.php';
if(in_array($audit['status'],['open','returned'],true)):?><form class="mt-4" method="post" action="/community-actions/<?=$e($context->propertySlug)?>/rm-audits/<?=$e($audit['public_id'])?>/complete" data-processing-form><input type="hidden" name="_token" value="<?=$e($csrfToken)?>"><p>Confirm all listed items have been corrected in Rent Manager.</p><button class="btn btn-success">Mark Completed</button></form><?php endif;
$contentHtml=(string)ob_get_clean();$pageTitle='RM Audit — NPM Gateway';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/community-actions',dirname(__DIR__,4));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,2).'/layouts/app.php';
