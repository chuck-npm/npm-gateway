<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
final class DashboardSummaryRepository implements DashboardSummaryStoreInterface
{
 public function __construct(private readonly mysqli $connection){}
 /** @return array{property_count:int,employee_count:int,user_count:int,active_user_count:int,active_assignment_count:int} */
 public function counts():array
 {
  $result=$this->connection->query("SELECT (SELECT COUNT(*) FROM properties) property_count,(SELECT COUNT(*) FROM employees) employee_count,(SELECT COUNT(*) FROM users) user_count,(SELECT COUNT(*) FROM users WHERE status='active') active_user_count,(SELECT COUNT(*) FROM employee_property_assignments WHERE starts_on<=CURRENT_DATE AND ends_on IS NULL) active_assignment_count");
  $row=$result->fetch_assoc();$result->free();
  return ['property_count'=>(int)$row['property_count'],'employee_count'=>(int)$row['employee_count'],'user_count'=>(int)$row['user_count'],'active_user_count'=>(int)$row['active_user_count'],'active_assignment_count'=>(int)$row['active_assignment_count']];
 }
}
