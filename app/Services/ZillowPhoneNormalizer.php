<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final readonly class ZillowPhoneNormalizer
{
 public function normalize(?string$value):?string{$digits=preg_replace('/\D/','',trim((string)$value))??'';if(strlen($digits)===11&&$digits[0]==='1')$digits=substr($digits,1);return preg_match('/^[0-9]{10}$/D',$digits)===1?$digits:null;}
}
