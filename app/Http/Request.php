<?php
declare(strict_types=1);
namespace NpmGateway\Http;
final readonly class Request
{
 /** @param array<string,string> $post @param array<string,string> $cookies @param array<string,string> $server @param array<string,string> $query */
 public function __construct(public string $method,public string $path,public array $post=[],public array $cookies=[],public array $server=[],public array $query=[]){}
 public function ip():string{return $this->server['REMOTE_ADDR']??'127.0.0.1';}
 public function agent():?string{return $this->server['HTTP_USER_AGENT']??null;}
}
