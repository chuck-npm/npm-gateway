<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
use NpmGateway\ValueObjects\EmployeeDirectoryProfile;
interface EmployeeDirectoryStoreInterface
{
    /** @return list<array<string,mixed>> */
    public function searchDirectory(EmployeeDirectoryCriteria $criteria):array;
    public function countDirectoryResults(EmployeeDirectoryCriteria $criteria):int;
    public function findDirectoryProfileByPublicId(string $publicId):?EmployeeDirectoryProfile;
}
