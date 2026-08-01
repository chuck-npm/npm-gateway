<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\ValueObjects\ToolCard;
final class CorporateToolsProvider implements CorporateToolsProviderInterface
{
    private readonly CorporateAccessService $access;
    public function __construct(?CorporateAccessService $access=null){$this->access=$access??new CorporateAccessService();}
    public function tools(AuthenticatedRequestContext $context):array
    {
        $definitions=[
            ['finance','Finance','Access credit card purchases, reconciliations, invoices, and financial reporting.','Finance',null,null],
            ['human-resources','Human Resources','Manage employees, time off, timecards, payroll documents, and personnel records.','Human Resources','/human-resources','hr.index'],
            ['marketing','Marketing','Manage property websites, advertising, leads, announcements, and marketing resources.','Marketing',null,null],
            ['admin','Admin','Manage properties, Gateway users, assignments, and system configuration.','Administration','/admin','admin.index'],
            ['credit-cards','Credit Cards','Manage employee credit-card assignments and records.','Credit Cards',null,null],
        ];
        return array_map(function(array $tool,int $index)use($context):ToolCard{
            [$key,$title,$description,$categoryLabel,$route,$routeName]=$tool;$allowed=$this->access->canAccessCategory($context,$key);$available=$route!==null&&$routeName!==null;$enabled=$allowed&&$available;
            $footer=$enabled?'Open '.$title:($allowed?'Module planned':'Access not assigned');$badge=$enabled?null:($allowed?'Planned':'Unavailable');$accessibility=$enabled?'Open '.$title:$title.', '.$footer;
            return new ToolCard($key,$title,$description,$categoryLabel,$footer,$enabled?$route:null,$enabled,($index+1)*10,$badge,$accessibility,$enabled?$routeName:null);
        },$definitions,array_keys($definitions));
    }
}
