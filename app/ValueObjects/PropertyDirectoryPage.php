<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class PropertyDirectoryPage
{
    public function __construct(public array $properties,public int $totalResults,public int $currentPage,public int $perPage,public int $totalPages,public PropertyDirectoryCriteria $criteria){}
}
