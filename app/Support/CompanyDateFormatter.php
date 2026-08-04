<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class CompanyDateFormatter
{
 public function format(string $value):string{$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $date instanceof \DateTimeImmutable&&$date->format('Y-m-d')===$value?$date->format('F j, Y'):$value;}
}
