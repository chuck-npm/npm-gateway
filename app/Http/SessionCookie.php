<?php
declare(strict_types=1);
namespace NpmGateway\Http;
use NpmGateway\Configuration\AuthenticationConfig;
final class SessionCookie
{
 public function __construct(private readonly AuthenticationConfig $config){}
 /** @return array<string,mixed> */
 public function set(string $token):array{return ['name'=>$this->config->cookieName,'value'=>$token,'expires'=>0,'path'=>'/','secure'=>$this->config->secure,'httponly'=>true,'samesite'=>$this->config->sameSite];}
 /** @return array<string,mixed> */
 public function clear():array{return ['name'=>$this->config->cookieName,'value'=>'','expires'=>1,'path'=>'/','secure'=>$this->config->secure,'httponly'=>true,'samesite'=>$this->config->sameSite];}
 public function read(Request $request):?string{return $request->cookies[$this->config->cookieName]??null;}
}
