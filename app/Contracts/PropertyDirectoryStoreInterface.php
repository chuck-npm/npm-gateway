<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\PropertyDirectoryCriteria;
interface PropertyDirectoryStoreInterface
{
    public function searchDirectory(PropertyDirectoryCriteria $criteria):array;
    public function countDirectoryResults(PropertyDirectoryCriteria $criteria):int;
}
