<?php
declare(strict_types=1);
namespace NpmGateway\Http;
final readonly class Response
{
 /** @param array<string,string> $headers @param list<array<string,mixed>> $cookies */
 public function __construct(public int $status,public string $body='',public array $headers=[],public array $cookies=[],public mixed $stream=null){}
 public static function redirect(string $to):self{return new self(303,'',['Location'=>$to]);}
 public static function json(array $data,int $status=200):self{return new self($status,json_encode($data,JSON_THROW_ON_ERROR),['Content-Type'=>'application/json; charset=UTF-8']);}
}
