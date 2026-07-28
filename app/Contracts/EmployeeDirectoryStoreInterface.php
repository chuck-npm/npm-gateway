<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
interface EmployeeDirectoryStoreInterface
{
    /** @return list<array<string,mixed>> */
    public function searchDirectory(EmployeeDirectoryCriteria $criteria):array;
    public function countDirectoryResults(EmployeeDirectoryCriteria $criteria):int;
}
