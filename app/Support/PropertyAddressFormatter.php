<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class PropertyAddressFormatter
{
    public function format(?string $street,?string $city,?string $state,?string $postalCode):string
    {
        $street=trim((string)$street);$city=trim((string)$city);$state=trim((string)$state);$postalCode=trim((string)$postalCode);
        $locality=trim(implode(' ',array_values(array_filter([$state,$postalCode],static fn(string $v):bool=>$v!==''))));
        return implode(', ',array_values(array_filter([$street,$city,$locality],static fn(string $v):bool=>$v!=='')));
    }
}
