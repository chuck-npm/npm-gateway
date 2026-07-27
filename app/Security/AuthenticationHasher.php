<?php
declare(strict_types=1);
namespace NpmGateway\Security;
use NpmGateway\Configuration\AuthenticationConfig;
final class AuthenticationHasher
{
 public function __construct(private readonly AuthenticationConfig $config){}
 public function session(string $raw):string{return hash_hmac('sha256',$raw,$this->config->deriveKey('gateway:session-token:v1'));}
 public function ip(string $ip):string{return hash_hmac('sha256',$ip,$this->config->deriveKey('gateway:auth-ip:v1'));}
 public function username(string $username):string{return hash_hmac('sha256',$username,$this->config->deriveKey('gateway:auth-username:v1'));}
}
