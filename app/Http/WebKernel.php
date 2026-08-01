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
final class WebKernel
{
 public function __construct(private readonly AuthenticationController $authentication,private readonly DashboardController $dashboard,private readonly RequireAuthenticationMiddleware $middleware,private readonly ?EmployeeWorkspaceController $employees=null,private readonly ?PropertyWorkspaceController $properties=null,private readonly ?HumanResourcesController $hr=null,private readonly ?HrEmployeeController $hrEmployees=null,private readonly ?AdminController $admin=null){}
 public function handle(Request $request,\DateTimeImmutable $now):Response
 {
  if($request->path==='/')return Response::redirect('/login');
  if($request->path==='/login'&&$request->method==='GET')return $this->authentication->loginForm();
  if($request->path==='/login'&&$request->method==='POST')return $this->authentication->login($request,$now);
  if($request->path==='/dashboard'&&$request->method==='GET')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->dashboard->index($c),$now);
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
