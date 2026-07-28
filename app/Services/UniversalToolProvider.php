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
            ['employee-directory','Employee Directory','Find company employees and approved contact information.','People & Contacts'],
            ['property-information','Property Information','View community addresses, contacts, websites, and operational details.','Communities'],
            ['company-documents','Company Documents','Access company forms, manuals, policies, and shared documents.','Documents'],
            ['announcements','Announcements','Review company-wide updates and important notices.','Communications'],
            ['credit-card-purchases','Credit Card Purchases','Submit receipts and review your company card purchases.','Finance'],
            ['large-file-transfers','Large File Transfers','Securely share large files with other Gateway users.','Files'],
            ['order-supplies','Order Supplies','Request commonly used property and office supplies.','Purchasing'],
            ['time-off-requests','Time Off Requests','Submit and review your paid-time-off requests.','Human Resources'],
            ['policies-procedures','Policies & Procedures','Find current NPM policies and operating procedures.','Documents'],
            ['training-library','Training Library','Access training material, guides, and instructional resources.','Training'],
            ['support-requests','Support Requests','Request operational assistance from corporate staff.','Operations Support'],
            ['help-desk','Help Desk','Report a Gateway or technology issue.','Technical Support'],
        ];
        return array_map(static fn(array $tool,int $index):ToolCard=>$tool[0]==='employee-directory'
            ?new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Open directory','/employees',true,($index+1)*10,null,'Open Employee Directory','employees.index')
            :new ToolCard($tool[0],$tool[1],$tool[2],$tool[3],'Not yet enabled',null,false,($index+1)*10,'Planned'),$definitions,array_keys($definitions));
    }
}
