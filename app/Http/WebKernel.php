<?php
declare(strict_types=1);
namespace NpmGateway\Http;
use NpmGateway\Http\Controllers\AuthenticationController;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Http\Middleware\RequireAuthenticationMiddleware;
final class WebKernel
{
 public function __construct(private readonly AuthenticationController $authentication,private readonly DashboardController $dashboard,private readonly RequireAuthenticationMiddleware $middleware){}
 public function handle(Request $request,\DateTimeImmutable $now):Response
 {
  if($request->path==='/')return Response::redirect('/login');
  if($request->path==='/login'&&$request->method==='GET')return $this->authentication->loginForm();
  if($request->path==='/login'&&$request->method==='POST')return $this->authentication->login($request,$now);
  if($request->path==='/dashboard'&&$request->method==='GET')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->dashboard->index($c),$now);
  if($request->path==='/logout'&&$request->method==='GET')return new Response(405,'Method Not Allowed',['Allow'=>'POST']);
  if($request->path==='/logout'&&$request->method==='POST')return $this->middleware->handle($request,fn(AuthenticatedRequestContext $c):Response=>$this->authentication->logout($request,$c->rawToken,$now),$now);
  return new Response(404,'Not Found');
 }
}
