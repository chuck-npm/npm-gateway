<?php
declare(strict_types=1);
namespace NpmGateway\Domain;
final class EmployeeClass
{
 public const CORPORATE='corporate';
 public const MANAGER='manager';
 public const MAINTENANCE='maintenance';
 public const NOTIFICATION_ELIGIBLE=[self::CORPORATE,self::MANAGER];
 private function __construct(){}
}
