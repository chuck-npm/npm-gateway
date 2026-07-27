<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class DashboardSummary
{
 public function __construct(public int $propertyCount,public int $employeeCount,public int $userCount,public int $activeUserCount,public int $activeAssignmentCount,public bool $initialSetup,public string $displayName,public string $jobTitle){}
}
