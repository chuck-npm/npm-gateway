<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class EmployeeEmergencyContactSchema
{
 public const MIGRATION='202608040013_employee_emergency_contacts';
 public const TABLE='employee_emergency_contacts';
 public const COLUMNS=['id','public_id','employee_id','first_name','last_name','relationship','primary_phone','alternate_phone','created_at','updated_at'];
 public const INDEXES=['PRIMARY','uq_employee_emergency_contacts_public_id','uq_employee_emergency_contacts_employee'];
 public const FOREIGN_KEY='fk_employee_emergency_contacts_employee';
}
