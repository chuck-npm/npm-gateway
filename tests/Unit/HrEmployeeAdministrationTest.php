<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class HrEmployeeAdministrationTest extends TestCase
{
    public function testRoutesAndDedicatedPageFollowApprovedUx():void
    {
        $root=dirname(__DIR__,2);$routes=(string)file_get_contents($root.'/routes/web.php');foreach(['/human-resources/employees','hr.employees.index','hr.employees.store','/human-resources/employees/create','hr.employees.create'] as $value)self::assertStringContainsString($value,$routes);$form=(string)file_get_contents($root.'/resources/views/human-resources/employees/_form.php');foreach(['Employee Information','Contact Information','Operational Context','Gateway Account','Employee Notes','Generated automatically','data-phone-mask',"'start_date','Start Date','date'","'date_of_birth','Date of Birth','date'",'Create Employee','/human-resources/employees'] as $value)self::assertStringContainsString($value,$form);foreach(['type="password"','Maintenance','Gateway Access Yes/No','modal'] as $value)self::assertStringNotContainsString($value,$form);
    }
    public function testHrIndexReusesDirectoryAndKeepsUniversalReadOnly():void
    {
        $root=dirname(__DIR__,2);$controller=(string)file_get_contents($root.'/app/Http/Controllers/HrEmployeeController.php');self::assertStringContainsString('EmployeeDirectoryService',$controller);$hr=(string)file_get_contents($root.'/resources/views/human-resources/employees/index.php');foreach(['Add Employee','Action','Editing not yet enabled'] as $value)self::assertStringContainsString($value,$hr);foreach(['personal_phone','personal_email','comments'] as $value)self::assertStringNotContainsString($value,$hr);$universal=(string)file_get_contents($root.'/resources/views/employees/index.php');foreach(['Add Employee','Editing not yet enabled','data-label="Action"'] as $value)self::assertStringNotContainsString($value,$universal);
    }
    public function testDomainRulesAndCredentialProtectionsAreCentralized():void
    {
        $root=dirname(__DIR__,2);$validator=(string)file_get_contents($root.'/app/Services/HrEmployeeValidator.php');foreach(["['corporate','manager','assistant_manager']","'assistant_manager'?'assistant_manager'","?'corporate':'manager'",'65535'] as $value)self::assertStringContainsString($value,$validator);$creation=(string)file_get_contents($root.'/app/Services/HrEmployeeCreationService.php');foreach(['employee_number_allocation','NPM','999999','PasswordService','hr.employee_notification_sent','hr.employee_notification_failed'] as $value)self::assertStringContainsString($value,$creation);self::assertStringNotContainsString("'password'=>",$creation);$repository=(string)file_get_contents($root.'/app/Repositories/EmployeeRepository.php');self::assertStringContainsString("CASE WHEN e.employee_class='corporate' THEN 'Corporate'",$repository);
    }
    public function testNotificationHasRequiredClassificationAndBodies():void
    {
        $root=dirname(__DIR__,2);$source=(string)file_get_contents($root.'/app/Services/HrEmployeeNotificationService.php').(string)file_get_contents($root.'/app/Services/HrEmployeeNotificationConfig.php');foreach(['SECURE','Initial Gateway Password','Employee Notes','Created By Gateway Username','$html','$text','no-reply@npmpropertiesinc.com'] as $value)self::assertStringContainsString($value,$source);self::assertStringNotContainsString('smtp_password',$source);
    }
    public function testSecureNotificationFormatsDobAndMissingOptionalValues():void
    {
        $notifier=new \NpmGateway\Notifications\InMemoryHrEmployeeNotifier();$service=new \NpmGateway\Services\HrEmployeeNotificationService($notifier,['host'=>'smtp.example.test','port'=>'587','username'=>'mailer','password'=>'TEST-secret','secure'=>'tls','from_address'=>'no-reply@npmpropertiesinc.com','from_name'=>'NPM Gateway','recipients'=>'hr@example.test','environment'=>'testing']);
        $service->send(['first_name'=>'Tim','last_name'=>'Tester','date_of_birth'=>'1985-08-14','personal_phone'=>null,'personal_email'=>null,'comments'=>null],'TEST-initial-password');$notice=$notifier->notices[0];
        foreach(['Date of Birth: 08/14/1985','Personal Phone: Not provided','Personal Email: Not provided','Employee Notes: Not provided'] as $text)self::assertStringContainsString($text,$notice->textBody);
        foreach(['Date of Birth','08/14/1985','Not provided'] as $text)self::assertStringContainsString($text,$notice->htmlBody);
        self::assertStringNotContainsString('1985',$notice->subject);self::assertSame('TEST-initial-password',$notice->initialPassword());
    }
    public function testUsernameSuggestionUsesSharedSemanticMarkupAndExternalScript():void
    {
        $root=dirname(__DIR__,2);$form=(string)file_get_contents($root.'/resources/views/human-resources/employees/_form.php');$footer=(string)file_get_contents($root.'/resources/views/components/footer.php');
        foreach(['data-username-source','data-username-target','data-username-preserved'] as $marker)self::assertStringContainsString($marker,$form);
        self::assertStringContainsString('<script type="module" src="/assets/js/employee-username.js"></script>',$footer);
        self::assertStringNotContainsString('<script>',$form.(string)file_get_contents($root.'/resources/views/human-resources/employees/create.php'));
        self::assertFileExists($root.'/public/assets/js/employee-username.js');
    }
    public function testBothEmployeeDirectoriesReuseGatewayAccessBadges():void
    {
        $root=dirname(__DIR__,2);$company=(string)file_get_contents($root.'/resources/views/employees/index.php');$hr=(string)file_get_contents($root.'/resources/views/human-resources/employees/index.php');$component=(string)file_get_contents($root.'/resources/views/components/gateway-access-badge.php');$repository=(string)file_get_contents($root.'/app/Repositories/EmployeeRepository.php');
        foreach([$company,$hr] as $view){self::assertSame(1,substr_count($view,"require \$components.'/gateway-access-badge.php'"));self::assertStringNotContainsString('style=',$view);}
        foreach(["u.id IS NULL THEN 'none'","u.status='active' THEN 'enabled'","ELSE 'disabled'"] as $mapping)self::assertStringContainsString($mapping,$repository);
        foreach(["'enabled'=>['Enabled','success']","'disabled'=>['Disabled','warning']","default=>['None','neutral']"] as $presentation)self::assertStringContainsString($presentation,$component);
    }
}
