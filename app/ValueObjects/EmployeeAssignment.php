<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeAssignment
{
    public function __construct(public string $propertyPublicId,public string $propertyName,public string $assignmentRole,public bool $primary,public string $startsOn) {}
}
