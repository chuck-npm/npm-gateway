<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\UniversalToolProviderInterface;
use NpmGateway\ValueObjects\ToolCard;
final class UniversalToolProvider implements UniversalToolProviderInterface
{
    public function tools(): array
    {
        $definitions=[
            ['employee-directory','Company Directory','Find employees and approved company contact information.','People & Contacts'],
            ['property-information','Property Information','View community addresses, contacts, websites, and operational details.','Communities'],
            ['community-actions','Community Actions','Daily property management tasks and operational activities.','Communities'],
            ['notifications','Notifications','Review company notices and required communications.','Communications'],
            ['credit-card-purchases','Credit Card Purchases','Submit receipts and review your company card purchases.','Finance'],
            ['large-file-transfers','Large File Transfers','Securely share large files with other Gateway users.','Files'],
            ['company-documents','Company Documents','Access company forms, manuals, policies, and shared documents.','Documents'],
            ['order-supplies','Order Supplies','Request commonly used property and office supplies.','Purchasing'],
        ];
        return array_map(static fn(array $tool,int $index):ToolCard=>$tool[0]==='employee-directory'
            ?new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Open directory','/employees',true,($index+1)*10,null,'Open Company Directory','employees.index')
            :($tool[0]==='property-information'?new ToolCard($tool[0],'Properties','View company property contact and operational information.',$tool[3],'Open properties','/properties',true,($index+1)*10,null,'Open properties','properties.index')
            :($tool[0]==='community-actions'?new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Open Community Actions','/community-actions',true,($index+1)*10,null,'Open Community Actions','community-actions.index')
            :($tool[0]==='notifications'?new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Open notifications','/notifications',true,($index+1)*10,null,'Open Notifications','notifications.index')
            :new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Not yet enabled',null,false,($index+1)*10,'Planned')))),$definitions,array_keys($definitions));
    }
}
