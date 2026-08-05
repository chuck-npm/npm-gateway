<?php
declare(strict_types=1);
use NpmGateway\Domain\EmployeeClass;
use NpmGateway\Exceptions\Domain\IneligibleNotificationRecipientException;
use NpmGateway\Services\GatewayNotificationRecipientEligibilityPolicy;
use PHPUnit\Framework\TestCase;
final class NotificationRecipientEligibilityPolicyTest extends TestCase
{
 private GatewayNotificationRecipientEligibilityPolicy $policy;
 protected function setUp():void{$this->policy=new GatewayNotificationRecipientEligibilityPolicy();}
 private function recipient(string $class='corporate',string $employee='active',string $user='active'):array{return ['employee_class'=>$class,'employment_status'=>$employee,'user_id'=>1,'user_status'=>$user,'business_email'=>'person@example.test'];}
 public function testOnlyActiveCorporateAndManagerEmployeesAreEligible():void
 {self::assertTrue($this->policy->isEligibleUser($this->recipient(EmployeeClass::CORPORATE)));self::assertTrue($this->policy->isEligibleUser($this->recipient(EmployeeClass::MANAGER)));foreach([[EmployeeClass::MAINTENANCE,'active','active'],['corporate','inactive','active'],['manager','inactive','active'],['maintenance','inactive','active'],['future_class','active','active']] as [$class,$employee,$user])self::assertFalse($this->policy->isEligibleUser($this->recipient($class,$employee,$user)));self::assertFalse($this->policy->isEligibleEmployee(null));}
 public function testInAppDeliveryRequiresExistingEnabledUser():void
 {$missing=$this->recipient();unset($missing['user_id']);self::assertFalse($this->policy->isEligibleUser($missing));self::assertFalse($this->policy->isEligibleUser($this->recipient('corporate','active','disabled')));}
 public function testCategoryAndSuppliedEmailCannotOverrideMaintenanceDenial():void
 {$recipient=$this->recipient('maintenance');$recipient['categories']=['admin','company-notices'];$recipient['business_email']='override@example.test';self::assertFalse($this->policy->isEligibleUser($recipient));self::assertSame([], $this->policy->filterEligibleRecipients([$recipient]));try{$this->policy->requireEligibleRecipient($recipient);self::fail('Maintenance target was accepted.');}catch(IneligibleNotificationRecipientException){self::addToAssertionCount(1);}}
 public function testCompleteResolvedSetFailsClosed():void
 {try{$this->policy->requireEligibleRecipients([$this->recipient('corporate'),$this->recipient('maintenance')]);self::fail('Unsafe set was accepted.');}catch(IneligibleNotificationRecipientException){self::addToAssertionCount(1);}}
 public function testCanonicalAllowlistIsCentralAndEnvironmentIndependent():void
 {self::assertSame(['corporate','manager'],EmployeeClass::NOTIFICATION_ELIGIBLE);self::assertArrayNotHasKey('NOTIFICATION_ELIGIBLE_CLASSES',$_ENV);}
}
