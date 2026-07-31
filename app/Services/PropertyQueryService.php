<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\PropertyDirectoryStoreInterface;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Support\PropertyAddressFormatter;
use NpmGateway\ValueObjects\PropertyDirectoryCriteria;
use NpmGateway\ValueObjects\PropertyDirectoryPage;
use NpmGateway\ValueObjects\PropertyDirectoryRow;
final class PropertyQueryService
{
    public function __construct(private readonly PropertyDirectoryStoreInterface $properties,private readonly PropertyAddressFormatter $addresses,private readonly PhoneFormatter $phones){}
    public function search(PropertyDirectoryCriteria $criteria):PropertyDirectoryPage
    {
        $total=$this->properties->countDirectoryResults($criteria);$pages=max(1,(int)ceil($total/$criteria->perPage));$page=min($criteria->page,$pages);
        if($page!==$criteria->page)$criteria=new PropertyDirectoryCriteria($criteria->search,$criteria->sort,$criteria->direction,$page,$criteria->perPage);
        $rows=array_map(fn(array $r):PropertyDirectoryRow=>new PropertyDirectoryRow((int)$r['prop_id'],(string)$r['display_name'],$this->addresses->format($r['address_line_1'],$r['city'],$r['state'],$r['postal_code']),$this->phones->format($r['office_phone']),$r['ivr_number']===null?'—':$this->phones->format($r['ivr_number']),(string)$r['manager_name']),$this->properties->searchDirectory($criteria));
        return new PropertyDirectoryPage($rows,$total,$page,$criteria->perPage,$pages,$criteria);
    }
}
