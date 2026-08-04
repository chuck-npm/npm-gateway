<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class LoginReturnStore
{
 private array $session;
 public function __construct(array &$session){$this->session=&$session;}
 public function remember(string $path):void{if($this->approved($path))$this->session['gateway_login_return']=['path'=>$path,'expires'=>time()+600];}
 public function consume():?string{$row=$this->session['gateway_login_return']??null;unset($this->session['gateway_login_return']);return is_array($row)&&($row['expires']??0)>=time()&&$this->approved((string)($row['path']??''))?(string)$row['path']:null;}
 public function approved(string $path):bool{return !preg_match('/[\r\n\x00-\x1F\x7F]/',$path)&&preg_match('#^/storage/[0-9A-HJKMNP-TV-Z]{26}(?:/image)?$#D',$path)===1;}
}
