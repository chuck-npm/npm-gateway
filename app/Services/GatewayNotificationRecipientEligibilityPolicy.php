<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Domain\EmployeeClass;
use NpmGateway\Exceptions\Domain\IneligibleNotificationRecipientException;
final class GatewayNotificationRecipientEligibilityPolicy
{
 public function isEligibleEmployee(?array $employee,bool $requireActive=true):bool
 {
  if($employee===null)return false;
  if(!in_array((string)($employee['employee_class']??''),EmployeeClass::NOTIFICATION_ELIGIBLE,true))return false;
  return !$requireActive||(string)($employee['employment_status']??'')==='active';
 }
 public function isEligibleUser(?array $recipient,bool $requireActiveEmployee=true,bool $requireActiveUser=true):bool
 {
  if(!$this->isEligibleEmployee($recipient,$requireActiveEmployee))return false;
  if($recipient===null||!isset($recipient['user_id']))return false;
  return !$requireActiveUser||(string)($recipient['user_status']??'')==='active';
 }
 public function filterEligibleRecipients(array $recipients,bool $requireActiveEmployee=true,bool $requireActiveUser=true):array
 {return array_values(array_filter($recipients,fn(mixed $r):bool=>is_array($r)&&$this->isEligibleUser($r,$requireActiveEmployee,$requireActiveUser)));}
 public function requireEligibleRecipient(?array $recipient,bool $requireActiveEmployee=true,bool $requireActiveUser=true):array
 {if(!$this->isEligibleUser($recipient,$requireActiveEmployee,$requireActiveUser))throw new IneligibleNotificationRecipientException();return $recipient;}
 public function requireEligibleRecipients(array $recipients,bool $requireActiveEmployee=true,bool $requireActiveUser=true):array
 {foreach($recipients as $recipient)$this->requireEligibleRecipient(is_array($recipient)?$recipient:null,$requireActiveEmployee,$requireActiveUser);return array_values($recipients);}
}
