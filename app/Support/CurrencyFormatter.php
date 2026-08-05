<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class CurrencyFormatter
{
 public static function usd(string $decimal):string{$negative=str_starts_with($decimal,'-');$value=ltrim($decimal,'+-');[$whole,$fraction]=array_pad(explode('.',$value,2),2,'00');$whole=ltrim($whole,'0');if($whole==='')$whole='0';$fraction=substr(str_pad($fraction,2,'0'),0,2);return ($negative?'-$':'$').number_format((int)$whole,0,'.',',').'.'.$fraction;}
}
