<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\ValueObjects\PropertyDirectoryCriteria;
final class PropertyDirectoryCriteriaFactory
{
    public function fromQuery(array $query):PropertyDirectoryCriteria
    {
        $search=trim($this->scalarString($query['search']??'', ''));
        $search=substr(preg_replace('/[\x00-\x1F\x7F]/u','',$search)??'',0,100);
        $requestedSort=$this->scalarString($query['sort']??'name','name');
        $sort=in_array($requestedSort,['prop_id','name','address','phone','ivr','manager'],true)?$requestedSort:'name';
        $requestedDirection=strtolower($this->scalarString($query['direction']??'asc','asc'));
        $direction=in_array($requestedDirection,['asc','desc'],true)?$requestedDirection:'asc';
        $requestedPage=$this->scalarString($query['page']??'1','1');$validatedPage=filter_var($requestedPage,FILTER_VALIDATE_INT);
        $page=$validatedPage===false?1:max(1,$validatedPage);
        $requestedPerPage=$this->scalarString($query['per_page']??'25','25');$validatedPerPage=filter_var($requestedPerPage,FILTER_VALIDATE_INT);
        $perPage=$validatedPerPage!==false&&in_array($validatedPerPage,[25,50,100],true)?$validatedPerPage:25;
        return new PropertyDirectoryCriteria($search,$sort,$direction,$page,$perPage);
    }
    private function scalarString(mixed $value,string $default):string{return is_scalar($value)?(string)$value:$default;}
}
