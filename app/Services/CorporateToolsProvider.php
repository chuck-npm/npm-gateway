<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\ValueObjects\ToolCard;
final class CorporateToolsProvider implements CorporateToolsProviderInterface
{
    public function tools():array
    {
        $definitions=[
            ['finance','Finance','Access credit card purchases, reconciliations, invoices, and financial reporting.','Finance'],
            ['human-resources','Human Resources','Manage employees, time off, timecards, payroll documents, and personnel records.','Human Resources'],
            ['marketing','Marketing','Manage property websites, advertising, leads, announcements, and marketing resources.','Marketing'],
            ['admin','Admin','Manage properties, Gateway users, assignments, and system configuration.','Administration'],
        ];
        return array_map(static fn(array $tool,int $index):ToolCard=>new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Module planned',null,false,($index+1)*10,'Planned'),$definitions,array_keys($definitions));
    }
}
