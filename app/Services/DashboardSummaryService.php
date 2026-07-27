<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\DashboardSummary;
final class DashboardSummaryService
{
 public function __construct(private readonly DashboardSummaryStoreInterface $repository){}
 public function forUser(AuthenticatedUser $user):DashboardSummary
 {
  $c=$this->repository->counts();
  return new DashboardSummary($c['property_count'],$c['employee_count'],$c['user_count'],$c['active_user_count'],$c['active_assignment_count'],$c['property_count']===0,$user->displayName,$user->jobTitle);
 }
}
