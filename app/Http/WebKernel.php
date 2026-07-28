<?php
declare(strict_types=1);
namespace NpmGateway\Http;
use NpmGateway\Http\Controllers\AuthenticationController;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Http\Middleware\RequireAuthenticationMiddleware;
use NpmGateway\Http\Controllers\EmployeeWorkspaceController;
final class WebKernel
{
 public function __construct(private readonly AuthenticationController $authentication,private readonly DashboardController $dashboard,private readonly RequireAuthenticationMiddleware $middleware,private readonly ?EmployeeWorkspaceController $employees=null){}
 public function handle(Request $request,\DateTimeImmutable $now):Response
 {
  if($request->path==='/')return Response::redirect('/login');
  if($request->path==='/login'&&$request->method==='GET')return $this->authentication->loginForm();
  if($request->path==='/login'&&$request->method==='POST')return $this->authentication->login($request,$now);
  if($request->path==='/dashboard'&&$request->method==='GET')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->dashboard->index($c),$now);
  if($request->path==='/employees'&&$request->method==='GET'&&$this->employees!==null)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->employees->index($request,$c),$now);
  if($request->method==='GET'&&$this->employees!==null&&preg_match('#^/employees/([^/]+)$#',$request->path,$match)===1)return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->employees->show($match[1],$c),$now);
  if($request->path==='/logout'&&$request->method==='GET')return new Response(405,'Method Not Allowed',['Allow'=>'POST']);
  if($request->path==='/logout'&&$request->method==='POST')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->authentication->logout($request,$c->rawToken,$now),$now);
  return new Response(404,'Not Found');
 }
}
