<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class DashboardHome
{
    /** @param list<ToolCard> $universalTools */
    public function __construct(public string $welcomeName,public string $employeeClassLabel,public string $jobTitle,public array $universalTools,public DashboardSummary $setupSummary) {}
}
