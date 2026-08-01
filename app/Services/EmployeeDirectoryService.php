<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\EmployeeDirectoryStoreInterface;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
use NpmGateway\ValueObjects\EmployeeDirectoryPage;
use NpmGateway\ValueObjects\EmployeeDirectoryRow;
final class EmployeeDirectoryService
{
    public function __construct(private readonly EmployeeDirectoryStoreInterface $employees,private readonly PhoneFormatter $phones) {}
    public function search(EmployeeDirectoryCriteria $criteria):EmployeeDirectoryPage
    {
        $total=$this->employees->countDirectoryResults($criteria);$pages=max(1,(int)ceil($total/$criteria->perPage));$page=min($criteria->page,$pages);
        if($page!==$criteria->page)$criteria=new EmployeeDirectoryCriteria($criteria->search,$criteria->employeeClass,$criteria->employmentStatus,$criteria->sort,$criteria->direction,$page,$criteria->perPage);
        $rows=array_map(fn(array $r):EmployeeDirectoryRow=>new EmployeeDirectoryRow((string)$r['employee_public_id'],(string)$r['employee_number'],(string)$r['display_name'],(string)$r['job_title'],(string)$r['employee_class'],(string)$r['employment_status'],$r['business_email']===null?null:(string)$r['business_email'],$r['company_phone']===null?null:$this->phones->format((string)$r['company_phone']),(string)$r['primary_property_name'],(string)$r['gateway_access_state']),$this->employees->searchDirectory($criteria));
        return new EmployeeDirectoryPage($rows,$total,$page,$criteria->perPage,$pages,$criteria);
    }
}
