<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Support\CompanyDateFormatter;
use NpmGateway\Support\PhoneFormatter;
final class NotificationPresentationService
{
 public function __construct(private readonly CompanyDateFormatter $dates,private readonly PhoneFormatter $phones){}
 public function employeeFields(array $payload):array{return ['employee_name'=>(string)($payload['employee_name']??''),'job_title'=>(string)($payload['job_title']??''),'start_date'=>$this->dates->format((string)($payload['start_date']??'')),'company_phone'=>$this->phones->format((string)($payload['company_phone']??'')),'business_email'=>(string)($payload['business_email']??''),'primary_property'=>(string)($payload['primary_property']??'')];}
}
