<?php
declare(strict_types=1);
namespace NpmGateway\Security;
final class CsrfService
{
 private array $session;
 public function __construct(array &$session){$this->session=&$session;}
 public function token():string{if(!isset($this->session['csrf']))$this->session['csrf']=bin2hex(random_bytes(32));return $this->session['csrf'];}
 public function valid(?string $token):bool{return is_string($token)&&isset($this->session['csrf'])&&hash_equals($this->session['csrf'],$token);}
}
