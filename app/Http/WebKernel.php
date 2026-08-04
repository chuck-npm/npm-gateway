<?php
declare(strict_types=1);
namespace NpmGateway\Http;
use NpmGateway\Http\Controllers\AuthenticationController;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Http\Middleware\RequireAuthenticationMiddleware;
use NpmGateway\Http\Controllers\EmployeeWorkspaceController;
use NpmGateway\Http\Controllers\PropertyWorkspaceController;
use NpmGateway\Http\Controllers\HumanResourcesController;
use NpmGateway\Http\Controllers\HrEmployeeController;
use NpmGateway\Http\Controllers\AdminController;
use NpmGateway\Http\Controllers\NotificationController;
use NpmGateway\Http\Controllers\CompanyNoticeController;
use NpmGateway\Http\Controllers\StorageController;
use NpmGateway\Http\Controllers\CorporateWorkspaceController;
final class WebKernel
{
 public function __construct(private readonly AuthenticationController $authentication,private readonly DashboardController $dashboard,private readonly RequireAuthenticationMiddleware $middleware,private readonly ?EmployeeWorkspaceController $employees=null,private readonly ?PropertyWorkspaceController $properties=null,private readonly ?HumanResourcesController $hr=null,private readonly ?HrEmployeeController $hrEmployees=null,private readonly ?AdminController $admin=null,private readonly ?NotificationController $notifications=null,private readonly ?CompanyNoticeController $companyNotices=null,private readonly ?StorageController $storage=null,private readonly ?CorporateWorkspaceController $corporate=null){}
 public function handle(Request $request,\DateTimeImmutable $now):Response
 {
  if($request->path==='/')return Response::redirect('/login');
  if($request->path==='/login'&&$request->method==='GET')return $this->authentication->loginForm();
  if($request->path==='/login'&&$request->method==='POST')return $this->authentication->login($request,$now);
  if($request->path==='/dashboard'&&$request->method==='GET')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->dashboard->index($c),$now);
  if($this->corporate&&$request->method==='GET'){$method=match($request->path){'/corporate/operations'=>'operations','/corporate/operations/inventory-cf'=>'inventoryCf','/corporate/operations/inventory-hr'=>'inventoryHr','/corporate/operations/development'=>'development','/corporate/marketing'=>'marketing','/corporate/marketing/flyers'=>'flyers',default=>null};if($method!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->corporate->{$method}($c),$now);}
  if(preg_match('#^/storage/([0-9A-HJKMNP-TV-Z]{26})(/image)?$#',$request->path,$m)===1&&$request->method==='GET'&&$this->storage)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->storage->download($m[1],isset($m[2])&&$m[2]==='/image',$c),$now);
  if($request->path==='/notifications'&&$request->method==='GET'&&$this->notifications!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->notifications->index($request,$c),$now);
  if(preg_match('#^/notifications/([0-9A-HJKMNP-TV-Z]{26})$#',$request->path,$m)===1&&$request->method==='GET'&&$this->notifications!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->notifications->show($m[1],$c),$now);
  if(preg_match('#^/notifications/([0-9A-HJKMNP-TV-Z]{26})/acknowledge$#',$request->path,$m)===1&&$request->method==='POST'&&$this->notifications!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->notifications->acknowledge($request,$m[1],$c),$now);
  if($request->path==='/company-notices'&&$request->method==='GET'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->index($c),$now);
  if($request->path==='/company-notices/create'&&$request->method==='GET'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->create($request,$c),$now);
  if($request->path==='/company-notices/preview'&&$request->method==='POST'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->preview($request,$c),$now);
  if($request->path==='/company-notices/discard'&&$request->method==='POST'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->discard($request,$c),$now);
  if($request->path==='/company-notices/uploads/attachments'&&$request->method==='POST'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->upload($request,'attachment',$c),$now);
  if($request->path==='/company-notices/uploads/images'&&$request->method==='POST'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->upload($request,'embedded_image',$c),$now);
  if(preg_match('#^/company-notices/uploads/([0-9A-HJKMNP-TV-Z]{26})/remove$#',$request->path,$m)===1&&$request->method==='POST'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->removeUpload($request,$m[1],$c),$now);
  if(preg_match('#^/company-notices/uploads/([0-9A-HJKMNP-TV-Z]{26})/(preview|download)$#',$request->path,$m)===1&&$request->method==='GET'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->temporary($m[1],$m[2]==='preview',$request,$c),$now);
  if($request->path==='/company-notices/publish'&&$request->method==='POST'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->publish($request,$c),$now);
  if(preg_match('#^/company-notices/([0-9A-HJKMNP-TV-Z]{26})$#',$request->path,$m)===1&&$request->method==='GET'&&$this->companyNotices)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->companyNotices->show($m[1],$c),$now);
  if($request->path==='/admin'&&$request->method==='GET'&&$this->admin!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->admin->index($c),$now);
  if($request->path==='/admin/category-access'&&$request->method==='GET'&&$this->admin!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->admin->categoryAccess($c),$now);
  if($request->path==='/admin/category-access'&&$request->method==='POST'&&$this->admin!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->admin->save($request,$c),$now);
  if($request->path==='/employees'&&$request->method==='GET'&&$this->employees!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->employees->index($request,$c),$now);
  if($request->path==='/properties'&&$request->method==='GET'&&$this->properties!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->properties->directory($request,$c),$now);
  if($request->path==='/human-resources'&&$request->method==='GET'&&$this->hr!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->hr->index($c),$now);
  if($request->path==='/human-resources/employees'&&$request->method==='GET'&&$this->hrEmployees!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->hrEmployees->index($request,$c),$now);
  if($request->path==='/human-resources/employees/create'&&$request->method==='GET'&&$this->hrEmployees!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->hrEmployees->create($c),$now);
  if($request->path==='/human-resources/employees'&&$request->method==='POST'&&$this->hrEmployees!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->hrEmployees->store($request,$c),$now);
  if($request->path==='/human-resources/properties'&&$request->method==='GET'&&$this->properties!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->properties->hrDirectory($request,$c),$now);
  if($request->path==='/human-resources/properties/create'&&$request->method==='GET'&&$this->properties!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->properties->create($c),$now);
  if($request->path==='/human-resources/properties'&&$request->method==='POST'&&$this->properties!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->properties->store($request,$c),$now);
  if($request->path==='/logout'&&$request->method==='GET')return new Response(405,'Method Not Allowed',['Allow'=>'POST']);
  if($request->path==='/logout'&&$request->method==='POST')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->authentication->logout($request,$c->rawToken,$now),$now);
  return new Response(404,'Not Found');
 }
}
