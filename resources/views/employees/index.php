<?php
declare(strict_types=1);
$components=dirname(__DIR__).'/components';$escape=static fn(string $v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$criteria=$directoryPage->criteria;
ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Company Directory','current'=>true]];require $components.'/breadcrumb.php';
$heading='Company Directory';$description='Find employee contact information quickly.';$eyebrow='Company Directory';$actionsHtml='';require $components.'/page-header.php';
?>
<section class="gateway-workspace" aria-labelledby="employee-results-title">
 <form class="gateway-directory-toolbar" method="get" action="/employees">
  <div class="gateway-directory-search"><label class="form-label" for="employee-search">Search employees</label><div class="gateway-directory-search__controls"><input class="form-control form-control-lg" id="employee-search" name="search" value="<?= $escape($criteria->search) ?>" placeholder="Enter an employee name"><button class="btn gateway-button gateway-button--primary" type="submit">Search</button></div></div>
  <div class="gateway-directory-filters" aria-label="Directory filters">
   <div><label class="form-label" for="employee-class">Employee class</label><select class="form-select" id="employee-class" name="class"><?php foreach(['all'=>'All classes','corporate'=>'Corporate','manager'=>'Manager','maintenance'=>'Maintenance'] as $value=>$label): ?><option value="<?= $value ?>"<?= $criteria->employeeClass===$value?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
   <div><label class="form-label" for="employee-status">Employment status</label><select class="form-select" id="employee-status" name="status"><?php foreach(['all'=>'All statuses','active'=>'Active','inactive'=>'Inactive'] as $value=>$label): ?><option value="<?= $value ?>"<?= $criteria->employmentStatus===$value?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
   <div><label class="form-label" for="employee-sort">Sort by</label><select class="form-select" id="employee-sort" name="sort"><?php foreach(['name'=>'Name','employee_number'=>'Employee number','job_title'=>'Job title','employee_class'=>'Class','status'=>'Status','primary_property'=>'Operational context'] as $value=>$label): ?><option value="<?= $value ?>"<?= $criteria->sort===$value?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
  </div>
  <input type="hidden" name="direction" value="<?= $escape($criteria->direction) ?>">
  <div class="gateway-directory-toolbar__actions"><button class="btn gateway-button gateway-button--secondary" type="submit">Apply filters</button><a class="btn gateway-button gateway-button--secondary" href="/employees">Clear filters</a></div>
 </form>
 <div class="gateway-directory-result-count"><h2 id="employee-results-title"><?= $directoryPage->totalResults ?> employee<?= $directoryPage->totalResults===1?'':'s' ?></h2><p>Approved operational directory information.</p></div>
 <?php if($directoryPage->employees===[]): ?>
  <div class="gateway-empty-state"><h3 class="gateway-empty-state__title"><?= $criteria->isFiltered()?'No employees match the current search and filters.':'No employees have been added yet.' ?></h3><?php if($criteria->isFiltered()): ?><p class="gateway-empty-state__action"><a href="/employees">Clear filters</a></p><?php endif; ?></div>
 <?php else: ?>
  <div class="table-responsive gateway-directory-table-wrap"><table class="table gateway-directory-table"><thead><tr><th scope="col">Employee</th><th scope="col">Job Title</th><th scope="col">Operational Context</th><th scope="col">Company Phone</th><th scope="col">Business Email</th><th scope="col">Class</th><th scope="col">Status</th><th scope="col">Gateway Access</th></tr></thead><tbody>
  <?php foreach($directoryPage->employees as $employee): ?><tr><td data-label="Employee"><strong class="gateway-employee-name"><?= $escape($employee->displayName) ?></strong></td><td data-label="Job Title"><?= $escape($employee->jobTitle) ?></td><td data-label="Operational Context"><?= $escape($employee->primaryPropertyName) ?></td><td data-label="Company Phone"><?= $employee->companyPhone===null?'Not provided':$escape($employee->companyPhone) ?></td><td data-label="Business Email"><?= $employee->businessEmail===null?'Not provided':$escape($employee->businessEmail) ?></td><td data-label="Class"><?= $escape(ucfirst($employee->employeeClass)) ?></td><td data-label="Status"><?= $escape(ucfirst($employee->employmentStatus)) ?></td><td data-label="Gateway Access"><?= $escape($employee->gatewayAccessStatus) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php $query=['search'=>$criteria->search,'class'=>$criteria->employeeClass,'status'=>$criteria->employmentStatus,'sort'=>$criteria->sort,'direction'=>$criteria->direction,'per_page'=>(string)$criteria->perPage,'page'=>'__PAGE__'];$pageUrlPattern='/employees?'.str_replace('__PAGE__','%d',http_build_query($query));$currentPage=$directoryPage->currentPage;$totalPages=$directoryPage->totalPages;require $components.'/pagination.php'; ?>
 <?php endif; ?>
</section>
<?php
$contentHtml=(string)ob_get_clean();$pageTitle='Company Directory — NPM Gateway';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/employees',dirname(__DIR__,3));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username.($user->jobTitle!==''?' · '.$user->jobTitle:'');$footerText='NPM Gateway — Internal use only';require dirname(__DIR__).'/layouts/app.php';
