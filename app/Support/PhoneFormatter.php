<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class PhoneFormatter
{
    public function normalize(string $value):?string
    {
        $digits=preg_replace('/\D/','',$value)??'';if(strlen($digits)===10)$digits='1'.$digits;
        return strlen($digits)===11&&$digits[0]==='1'?'+'.$digits:null;
    }
    public function format(?string $value):string
    {
        $digits=preg_replace('/\D/','',(string)$value)??'';if(strlen($digits)===11&&$digits[0]==='1')$digits=substr($digits,1);
        return strlen($digits)===10?sprintf('(%s) %s-%s',substr($digits,0,3),substr($digits,3,3),substr($digits,6)):(string)$value;
    }
}
