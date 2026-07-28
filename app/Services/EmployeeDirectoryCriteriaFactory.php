<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
final class EmployeeDirectoryCriteriaFactory
{
    /** @param array<string,string> $query */
    public function fromQuery(array $query):EmployeeDirectoryCriteria
    {
        $search=trim((string)($query['search']??''));
        $search=preg_replace('/[\x00-\x1F\x7F]/u','',$search)??'';
        $search=substr($search,0,100);
        $rawClass=$query['class']??'all';$class=in_array($rawClass,['all','corporate','manager','maintenance'],true)?$rawClass:'all';
        $rawStatus=$query['status']??'all';$status=in_array($rawStatus,['all','active','inactive'],true)?$rawStatus:'all';
        $rawSort=$query['sort']??'name';$sort=in_array($rawSort,['employee_number','name','job_title','employee_class','status','primary_property'],true)?$rawSort:'name';
        $direction=in_array(strtolower($query['direction']??'asc'),['asc','desc'],true)?strtolower($query['direction']??'asc'):'asc';
        $page=max(1,filter_var($query['page']??1,FILTER_VALIDATE_INT)?:1);
        $perPage=(int)($query['per_page']??25);if(!in_array($perPage,[25,50,100],true))$perPage=25;
        return new EmployeeDirectoryCriteria($search,$class,$status,$sort,$direction,$page,$perPage);
    }
}
