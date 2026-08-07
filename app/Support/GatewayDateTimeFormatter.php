<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class GatewayDateTimeFormatter
{
 public static function format(string $value):string{try{return (new \DateTimeImmutable($value))->format('F j, Y \a\t g:i A');}catch(\Throwable){return $value;}}
}
