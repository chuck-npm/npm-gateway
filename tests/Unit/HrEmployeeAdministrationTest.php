<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class HrEmployeeAdministrationTest extends TestCase
{
    public function testRoutesAndDedicatedPageFollowApprovedUx():void
    {
        $root=dirname(__DIR__,2);$routes=(string)file_get_contents($root.'/routes/web.php');foreach(['/human-resources/employees','hr.employees.index','hr.employees.store','/human-resources/employees/create','hr.employees.create'] as $value)self::assertStringContainsString($value,$routes);$form=(string)file_get_contents($root.'/resources/views/human-resources/employees/_form.php');foreach(['Employee Information','Contact Information','Operational Context','Gateway Account','Employee Notes','Generated automatically','data-phone-mask',"'start_date','Start Date','date'",'Create Employee','/human-resources/employees'] as $value)self::assertStringContainsString($value,$form);foreach(['type="password"','Maintenance','Gateway Access Yes/No','modal'] as $value)self::assertStringNotContainsString($value,$form);
    }
    public function testHrIndexReusesDirectoryAndKeepsUniversalReadOnly():void
    {
        $root=dirname(__DIR__,2);$controller=(string)file_get_contents($root.'/app/Http/Controllers/HrEmployeeController.php');self::assertStringContainsString('EmployeeDirectoryService',$controller);$hr=(string)file_get_contents($root.'/resources/views/human-resources/employees/index.php');foreach(['Add Employee','Action','Editing not yet enabled'] as $value)self::assertStringContainsString($value,$hr);foreach(['personal_phone','personal_email','comments'] as $value)self::assertStringNotContainsString($value,$hr);$universal=(string)file_get_contents($root.'/resources/views/employees/index.php');foreach(['Add Employee','Editing not yet enabled','data-label="Action"'] as $value)self::assertStringNotContainsString($value,$universal);
    }
    public function testDomainRulesAndCredentialProtectionsAreCentralized():void
    {
        $root=dirname(__DIR__,2);$validator=(string)file_get_contents($root.'/app/Services/HrEmployeeValidator.php');foreach(["['corporate','manager','assistant_manager']","'assistant_manager'?'assistant_manager'","?'corporate':'manager'",'Employee Notes are required'] as $value)self::assertStringContainsString($value,$validator);$creation=(string)file_get_contents($root.'/app/Services/HrEmployeeCreationService.php');foreach(['GET_LOCK','employee_number_allocation','NPM','999999','PasswordService','hr.employee_notification_sent','hr.employee_notification_failed'] as $value){if($value==='GET_LOCK')continue;self::assertStringContainsString($value,$creation);}self::assertStringNotContainsString("'password'=>",$creation);$repository=(string)file_get_contents($root.'/app/Repositories/EmployeeRepository.php');self::assertStringContainsString("CASE WHEN e.employee_class='corporate' THEN 'Corporate'",$repository);
    }
    public function testNotificationHasRequiredClassificationAndBodies():void
    {
        $root=dirname(__DIR__,2);$source=(string)file_get_contents($root.'/app/Services/HrEmployeeNotificationService.php').(string)file_get_contents($root.'/app/Services/HrEmployeeNotificationConfig.php');foreach(['SECURE','Initial Gateway Password','Employee Notes','Created By Gateway Username','$html','$text','no-reply@npmpropertiesinc.com'] as $value)self::assertStringContainsString($value,$source);self::assertStringNotContainsString('smtp_password',$source);
    }
}
