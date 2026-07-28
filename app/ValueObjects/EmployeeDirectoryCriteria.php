<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeDirectoryCriteria
{
    public function __construct(public string $search='',public string $employeeClass='all',public string $employmentStatus='all',public string $sort='name',public string $direction='asc',public int $page=1,public int $perPage=25) {}
    public function isFiltered():bool{return $this->search!==''||$this->employeeClass!=='all'||$this->employmentStatus!=='all';}
}
