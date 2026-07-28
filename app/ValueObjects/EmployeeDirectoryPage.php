<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeDirectoryPage
{
    /** @param list<EmployeeDirectoryRow> $employees */
    public function __construct(public array $employees,public int $totalResults,public int $currentPage,public int $perPage,public int $totalPages,public EmployeeDirectoryCriteria $criteria) {}
}
