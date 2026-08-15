<?php
declare(strict_types=1);
$components=dirname(__DIR__,2).'/components';$e=static fn(mixed$value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$criteria=$page['criteria'];ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Administration','url'=>'/admin'],['label'=>'Call Logs','current'=>true]];require$components.'/breadcrumb.php';
$heading='Call Logs';$description='Imported Lumen IVR call activity.';$eyebrow='Administration';$actionsHtml='<a class="btn gateway-button gateway-button--primary" href="/admin/call-logs/upload">Upload Call Log</a>';require$components.'/page-header.php';
if($success!==''):?><div class="alert gateway-alert gateway-alert--success" role="status"><?=$e($success)?></div><?php endif;
if($error!==''):?><div class="alert gateway-alert gateway-alert--danger" role="alert"><?=$e($error)?></div><?php endif;
if($criteria->errors!==[]):?><div class="alert gateway-alert gateway-alert--danger" role="alert"><?php foreach($criteria->errors as$message):?><div><?=$e($message)?></div><?php endforeach?></div><?php endif?>
<form class="gateway-form-card mb-3" method="get" action="/admin/call-logs">
 <input type="hidden" name="per_page" value="<?=$page['per_page']?>">
 <div class="row g-3 align-items-end">
  <label class="form-label col-12 col-md-3 mb-0">From Date<input class="form-control mt-1" type="date" name="from" value="<?=$e($criteria->fromDate)?>"></label>
  <label class="form-label col-12 col-md-3 mb-0">To Date<input class="form-control mt-1" type="date" name="to" value="<?=$e($criteria->toDate)?>"></label>
  <label class="form-label col-12 col-md-4 mb-0">Property<select class="form-select mt-1" name="property"><option value="">All Properties</option><?php foreach($page['destinations']as$destination):?><option value="<?=$e($destination['public_id'])?>"<?=$criteria->destinationPublicId===$destination['public_id']?' selected':''?>><?=$e($destination['display_name'])?></option><?php endforeach?></select></label>
  <div class="col-12 col-md-2 d-flex flex-wrap gap-2"><button class="btn gateway-button gateway-button--primary" type="submit">View Results</button><?php if($criteria->active()):?><a class="btn gateway-button gateway-button--secondary" href="/admin/call-logs">Clear Filters</a><?php endif?></div>
 </div>
</form>
<form class="d-flex flex-wrap align-items-end gap-2 mb-0" method="get" action="/admin/call-logs">
 <?php if($criteria->fromDate!==''):?><input type="hidden" name="from" value="<?=$e($criteria->fromDate)?>"><?php endif?>
 <?php if($criteria->toDate!==''):?><input type="hidden" name="to" value="<?=$e($criteria->toDate)?>"><?php endif?>
 <?php if($criteria->destinationPublicId!==''):?><input type="hidden" name="property" value="<?=$e($criteria->destinationPublicId)?>"><?php endif?>
 <label class="form-label mb-0">Calls per page<select class="form-select mt-1" name="per_page"><?php foreach([100,250,500]as$size):?><option value="<?=$size?>"<?=$page['per_page']===$size?' selected':''?>><?=$size?></option><?php endforeach?></select></label>
 <button class="btn btn-secondary" type="submit">Apply</button>
</form>
<?php if($page['all_total']>0&&$criteria->errors===[]):?>
 <div class="mt-3 mb-3" role="status"><strong><?=number_format($page['total'])?> <?=$page['total']===1?'call':'calls'?><?=$criteria->active()?' found':''?></strong><?php if($page['total']>0):?><div class="text-body-secondary"><?=number_format($page['from'])?>–<?=number_format($page['to'])?> of <?=number_format($page['total'])?></div><?php endif?></div>
<?php endif?>
<?php if($page['rows']===[]):
 if($page['all_total']===0){$emptyTitle='No call records have been imported.';$emptyMessage='Upload a Lumen Detailed Report to begin.';$emptyActionHtml='<a class="btn gateway-button gateway-button--primary" href="/admin/call-logs/upload">Upload Call Log</a>';}else{$emptyTitle='No calls found for the selected criteria.';$emptyMessage='Adjust the date or property filters and view results again.';$emptyActionHtml=$criteria->active()?'<a class="btn gateway-button gateway-button--secondary" href="/admin/call-logs">Clear Filters</a>':'';}$emptyIconHtml='';require$components.'/empty-state.php';
else:?><div class="table-responsive"><table class="table gateway-directory-table"><thead><tr><th scope="col">Property</th><th scope="col">Calling TN</th><th scope="col">Called TN</th><th scope="col">Start Time</th><th scope="col">Release Time</th><th scope="col">Duration (Seconds)</th></tr></thead><tbody><?php foreach($page['rows']as$row):?><tr><td><?=$e($row['property_name'])?></td><td><?=$e($phones->format($row['calling_tn']))?></td><td><?=$e($phones->format($row['called_tn']))?></td><td><?=$e(\NpmGateway\Support\GatewayDateTimeFormatter::format($row['started_at']))?></td><td><?=$e(\NpmGateway\Support\GatewayDateTimeFormatter::format($row['released_at']))?></td><td><?=$e($row['call_duration_seconds'])?></td></tr><?php endforeach?></tbody></table></div><?php
 $query=['from'=>$criteria->fromDate,'to'=>$criteria->toDate,'property'=>$criteria->destinationPublicId,'per_page'=>(string)$page['per_page'],'page'=>'__PAGE__'];$query=array_filter($query,static fn(string$value):bool=>$value!=='');$pageUrlPattern='/admin/call-logs?'.str_replace('__PAGE__','%d',http_build_query($query));$currentPage=$page['page'];$totalPages=$page['pages'];require$components.'/pagination.php';
endif;
$contentHtml=(string)ob_get_clean();$pageTitle='Call Logs — NPM Gateway';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/admin/call-logs',dirname(__DIR__,4));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,2).'/layouts/app.php';
