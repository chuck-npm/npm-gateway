<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\UniversalToolProviderInterface;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\DashboardHome;
use NpmGateway\Http\AuthenticatedRequestContext;
final class DashboardHomeService
{
    public function __construct(private readonly DashboardSummaryService $summaries,private readonly UniversalToolProviderInterface $universalTools,private readonly CorporateToolsProviderInterface $corporateTools,private readonly CorporateAccessService $corporateAccess) {}
    public function forRequest(AuthenticatedRequestContext $context):DashboardHome
    {
        $user=$context->user;
        return new DashboardHome($user->displayName,$user->employeeClass===''?'Gateway User':ucwords(str_replace(['-','_'],' ',$user->employeeClass)),$user->jobTitle,$this->universalTools->tools(),$this->corporateTools->tools($context),$this->summaries->forUser($user));
    }
}
