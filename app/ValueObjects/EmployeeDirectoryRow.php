<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeDirectoryRow
{
    public function __construct(public string $employeePublicId,public string $employeeNumber,public string $displayName,public string $jobTitle,public string $employeeClass,public string $employmentStatus,public ?string $businessEmail,public ?string $companyPhone,public string $primaryPropertyName,public string $gatewayAccessStatus) {}
}
