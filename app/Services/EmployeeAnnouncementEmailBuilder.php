<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Support\CompanyDateFormatter;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\ValueObjects\CompanyAnnouncementEmail;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
final class EmployeeAnnouncementEmailBuilder
{
 public function __construct(private readonly CompanyDateFormatter $dates,private readonly PhoneFormatter $phones){}
 public function build(EmployeeAnnouncement $a):CompanyAnnouncementEmail{$p=$a->payload;return new CompanyAnnouncementEmail('NPM GATEWAY','COMPANY ANNOUNCEMENT','New Employee','A new employee has joined NPM Properties.',[['label'=>'Employee Name','value'=>(string)$p['employee_name']],['label'=>'Job Title','value'=>(string)$p['job_title']],['label'=>'Start Date','value'=>$this->dates->format((string)$p['start_date'])],['label'=>'Company Phone','value'=>$this->phones->format((string)$p['company_phone'])],['label'=>'Business Email','value'=>(string)$p['business_email'],'type'=>'email'],['label'=>'Primary Property','value'=>(string)$p['primary_property']]],'Please welcome '.$p['employee_name'].' to the NPM team.','NPM Gateway — Internal company communication');}
}
