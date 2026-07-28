<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeDirectoryProfile
{
    /** @param list<EmployeeAssignment> $assignments */
    public function __construct(public string $employeePublicId,public string $employeeNumber,public string $fullName,public string $jobTitle,public string $employeeClass,public string $employmentStatus,public ?string $businessEmail,public ?string $companyPhone,public string $gatewayAccessStatus,public array $assignments) {}
}
