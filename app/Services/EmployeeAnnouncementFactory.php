<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
final class EmployeeAnnouncementFactory
{
 public function create(string $employeePublicId,array $data):EmployeeAnnouncement
 {
  $payload=['employee_name'=>trim((string)$data['first_name'].' '.(string)$data['last_name']),'job_title'=>(string)$data['job_title'],'start_date'=>(string)$data['start_date'],'company_phone'=>(string)$data['company_phone'],'business_email'=>(string)$data['business_email'],'primary_property'=>(string)$data['context_name']];
  return new EmployeeAnnouncement($employeePublicId,$payload);
 }
}
