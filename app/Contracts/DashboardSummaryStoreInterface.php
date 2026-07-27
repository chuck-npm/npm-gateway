<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface DashboardSummaryStoreInterface
{
 /** @return array{property_count:int,employee_count:int,user_count:int,active_user_count:int,active_assignment_count:int} */
 public function counts():array;
}
