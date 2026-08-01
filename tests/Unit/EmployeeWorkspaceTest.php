<?php
declare(strict_types=1);
use NpmGateway\Contracts\EmployeeDirectoryStoreInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\EmployeeWorkspaceController;
use NpmGateway\Http\Request;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\Services\EmployeeDirectoryCriteriaFactory;
use NpmGateway\Services\EmployeeDirectoryService;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
use PHPUnit\Framework\TestCase;
final class EmployeeWorkspaceTest extends TestCase
{
    public function testCriteriaFactoryNormalizesAndWhitelistsInput():void
    {
        $factory=new EmployeeDirectoryCriteriaFactory();$defaults=$factory->fromQuery([]);
        self::assertSame(['','all','all','name','asc',1,25],[$defaults->search,$defaults->employeeClass,$defaults->employmentStatus,$defaults->sort,$defaults->direction,$defaults->page,$defaults->perPage]);
        $valid=$factory->fromQuery(['search'=>"  Chuck\x00 ".str_repeat('x',120),'class'=>'manager','status'=>'inactive','sort'=>'primary_property','direction'=>'DESC','page'=>'2','per_page'=>'100']);
        self::assertStringStartsWith('Chuck ',$valid->search);self::assertLessThanOrEqual(100,strlen($valid->search));self::assertStringNotContainsString("\x00",$valid->search);self::assertSame(['manager','inactive','primary_property','desc',2,100],[$valid->employeeClass,$valid->employmentStatus,$valid->sort,$valid->direction,$valid->page,$valid->perPage]);
        $safe=$factory->fromQuery(['class'=>'admin','status'=>'terminated','sort'=>'password_hash','direction'=>'sideways','page'=>'-9','per_page'=>'1000']);
        self::assertSame(['all','all','name','asc',1,25],[$safe->employeeClass,$safe->employmentStatus,$safe->sort,$safe->direction,$safe->page,$safe->perPage]);self::assertTrue((new ReflectionClass($safe))->isReadOnly());
    }
    public function testServiceReturnsTypedPrivacyBoundedDirectoryResults():void
    {
        $store=new FakeEmployeeDirectoryStore();$service=new EmployeeDirectoryService($store,new PhoneFormatter());$page=$service->search(new EmployeeDirectoryCriteria());
        self::assertSame(1,$page->totalResults);self::assertCount(1,$page->employees);self::assertSame('enabled',$page->employees[0]->gatewayAccessState);self::assertSame('(570) 213-3312',$page->employees[0]->companyPhone);self::assertTrue((new ReflectionClass($page))->isReadOnly());
        foreach(['personalEmail','personalPhone','passwordHash','sessionToken','id','startsOn','assignments'] as $field)self::assertFalse(property_exists($page->employees[0],$field));
    }
    public function testControllerRendersApprovedReadOnlyWorkspaceAndProfile():void
    {
        $state=[];$controller=$this->controller($state);$context=new AuthenticatedRequestContext($this->user(),'TEST-token');
        $index=$controller->index(new Request('GET','/employees',[],[],[],['search'=>'Test']),$context);
        self::assertSame(200,$index->status);
        foreach(['Company Directory','Find employee contact information quickly.','placeholder="Enter an employee name"','method="get" action="/employees"','Operational Context','Company Phone','Business Email','Gateway Access','work@example.test','(570) 213-3312','gateway-status gateway-status--success','Enabled','>Active<','method="post" action="/logout"'] as $expected)self::assertStringContainsString($expected,$index->body);self::assertStringNotContainsString('5702133312',$index->body);self::assertStringNotContainsString('style=',$index->body);
        self::assertLessThan(strpos($index->body,'id="employee-class"'),strpos($index->body,'id="employee-search"'));
        foreach(['Employee Workspace','NPM000001','personal_email','personal phone','password_hash','Add Employee','Edit Employee','Delete','Export Employees','<img','avatar','TEST-token','>View<','data-label="Action"'] as $forbidden)self::assertStringNotContainsString($forbidden,$index->body);
    }
    public function testArchitectureHasNoWritesPrivateFieldsOrSqlOutsideRepository():void
    {
        $root=dirname(__DIR__,2);$controller=file_get_contents($root.'/app/Http/Controllers/EmployeeWorkspaceController.php');$service=file_get_contents($root.'/app/Services/EmployeeDirectoryService.php');$repository=file_get_contents($root.'/app/Repositories/EmployeeRepository.php');
        self::assertDoesNotMatchRegularExpression('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b/i',$controller);self::assertStringNotContainsString('Repositories\\',$controller);
        self::assertDoesNotMatchRegularExpression('/\\b(?:INSERT|UPDATE|DELETE|begin_transaction|commit|rollback)\\b/i',$service);
        $directorySource=substr($repository,(int)strpos($repository,'public function searchDirectory'));foreach(['personal_email','personal_phone','password_hash','failed_login_count','user_sessions'] as $private)self::assertStringNotContainsString($private,$directorySource);
        self::assertStringContainsString('prepare(',$repository);self::assertStringContainsString('$orders=[',$repository);
        $routes=file_get_contents($root.'/app/Http/WebKernel.php');foreach(["method==='POST'&&preg_match('#^/employees","method==='PUT'","method==='PATCH'","method==='DELETE'"] as $write)self::assertStringNotContainsString($write,$routes);
        self::assertStringNotContainsString('/employees/{publicId}',file_get_contents($root.'/routes/web.php'));self::assertStringNotContainsString('function show(',$controller);
        self::assertFileDoesNotExist($root.'/resources/views/employees/show.php');self::assertFileDoesNotExist($root.'/app/ValueObjects/EmployeeDirectoryProfile.php');self::assertFileDoesNotExist($root.'/app/Exceptions/Domain/EmployeeNotFoundException.php');
        self::assertFileDoesNotExist($root.'/database/migrations/202607270003_employee_workspace.php');
    }
    public function testConfirmedFutureRulesAreDocumentedWithoutImplementation():void
    {
        $root=dirname(__DIR__,2);$documentation=file_get_contents($root.'/README.md').file_get_contents($root.'/docs/architecture/GATEWAY_CONSTITUTION.md').file_get_contents($root.'/docs/data-dictionary.md');
        foreach(['NPM######','NPM999999','advisory lock','never reused','automatically receive Gateway','maintenance employees never','Transfers','assignments','Corporate is a valid future operational context','Employee photos'] as $rule)self::assertStringContainsStringIgnoringCase($rule,$documentation);
        $application=implode('',array_map('file_get_contents',glob($root.'/app/**/*.php')?:[]));self::assertStringNotContainsString('SELECT MAX',$application);self::assertStringNotContainsString('profile_photo',$application);
    }
    /** @param array<string,mixed> $state */
    private function controller(array &$state):EmployeeWorkspaceController
    {
        return new EmployeeWorkspaceController(new EmployeeDirectoryCriteriaFactory(),new EmployeeDirectoryService(new FakeEmployeeDirectoryStore(),new PhoneFormatter()),new CorporateAccessService([]),new CorporateToolsProvider(),new CsrfService($state),dirname(__DIR__,2).'/resources/views');
    }
    private function user():AuthenticatedUser{return new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'tester','Test User','Tester','manager');}
}
final class FakeEmployeeDirectoryStore implements EmployeeDirectoryStoreInterface
{
    public function searchDirectory(EmployeeDirectoryCriteria $criteria):array{return [['employee_public_id'=>str_repeat('A',26),'employee_number'=>'NPM000001','display_name'=>'Test Employee','job_title'=>'Tester','employee_class'=>'manager','employment_status'=>'active','business_email'=>'work@example.test','company_phone'=>'5702133312','primary_property_name'=>'Not assigned','gateway_access_state'=>'enabled']];}
    public function countDirectoryResults(EmployeeDirectoryCriteria $criteria):int{return 1;}
}
