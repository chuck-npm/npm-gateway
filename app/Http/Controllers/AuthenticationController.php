<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Exceptions\Domain\InvalidCredentialsException;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Http\SessionCookie;
use NpmGateway\Security\CsrfService;
use NpmGateway\Contracts\AuthenticationServiceInterface;
use NpmGateway\Contracts\SessionServiceInterface;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\ValueObjects\LoginRequest;
use NpmGateway\Services\LoginReturnStore;
final class AuthenticationController
{
 public function __construct(private readonly AuthenticationServiceInterface $authentication,private readonly SessionServiceInterface $sessions,private readonly SessionCookie $cookie,private readonly CsrfService $csrf,private readonly string $views,private readonly ?LoginReturnStore $returns=null){}
 public function loginForm(?string $error=null):Response{return new Response(200,$this->render('auth/login.php',['csrfToken'=>$this->csrf->token(),'error'=>$error]));}
 public function login(Request $request,\DateTimeImmutable $now):Response
 {
  if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');
  try{$result=$this->authentication->authenticate(new LoginRequest($request->post['username']??'',$request->post['password']??''),new ClientContext($request->ip(),$request->agent(),$now));$r=Response::redirect($this->returns?->consume()??'/dashboard');return new Response($r->status,'',$r->headers,[$this->cookie->set($result->session->reveal())]);}
  catch(InvalidCredentialsException){return $this->loginForm('We could not sign you in with the information provided.');}
 }
 public function logout(Request $request,string $raw,\DateTimeImmutable $now):Response
 {
  if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');
  $this->sessions->logout($raw,new ClientContext($request->ip(),$request->agent(),$now));$r=Response::redirect('/login');return new Response($r->status,'',$r->headers,[$this->cookie->clear()]);
 }
 private function render(string $file,array $data):string{extract($data,EXTR_SKIP);ob_start();require $this->views.'/'.$file;return (string)ob_get_clean();}
}
