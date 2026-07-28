<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\UniversalToolProviderInterface;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\DashboardHome;
final class DashboardHomeService
{
    public function __construct(private readonly DashboardSummaryService $summaries,private readonly UniversalToolProviderInterface $tools) {}
    public function forUser(AuthenticatedUser $user):DashboardHome
    {
        return new DashboardHome($user->displayName,$user->employeeClass===''?'Gateway User':ucwords(str_replace(['-','_'],' ',$user->employeeClass)),$user->jobTitle,$this->tools->tools(),$this->summaries->forUser($user));
    }
}
