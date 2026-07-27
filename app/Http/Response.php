<?php
declare(strict_types=1);
namespace NpmGateway\Http;
final readonly class Response
{
 /** @param array<string,string> $headers @param list<array<string,mixed>> $cookies */
 public function __construct(public int $status,public string $body='',public array $headers=[],public array $cookies=[]){}
 public static function redirect(string $to):self{return new self(303,'',['Location'=>$to]);}
}
