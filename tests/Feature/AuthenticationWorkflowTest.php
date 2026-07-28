<?php
declare(strict_types=1);
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Contracts\AuthenticationServiceInterface;
use NpmGateway\Contracts\SessionServiceInterface;
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Exceptions\Domain\InvalidCredentialsException;
use NpmGateway\Exceptions\Domain\InvalidSessionException;
use NpmGateway\Http\Controllers\AuthenticationController;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Http\Controllers\EmployeeWorkspaceController;
use NpmGateway\Http\Middleware\RequireAuthenticationMiddleware;
use NpmGateway\Http\Request;
use NpmGateway\Http\SessionCookie;
use NpmGateway\Http\WebKernel;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Services\UniversalToolProvider;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\EmployeeDirectoryCriteriaFactory;
use NpmGateway\Services\EmployeeDirectoryService;
use NpmGateway\Contracts\EmployeeDirectoryStoreInterface;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
use NpmGateway\ValueObjects\EmployeeDirectoryProfile;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\AuthenticationResult;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\ValueObjects\LoginRequest;
use NpmGateway\ValueObjects\SessionToken;
use NpmGateway\ValueObjects\SessionValidationResult;
use PHPUnit\Framework\TestCase;
final class AuthenticationWorkflowTest extends TestCase
{
 private array $state=[];private FakeBrowserAuthentication $authentication;private FakeBrowserSessions $sessions;private WebKernel $kernel;
 protected function setUp():void
 {
  $config=new AuthenticationConfig('npm_gateway_session',false,true,'Lax',60,8,15,5,5,15,10,10,str_repeat('K',32));$csrf=new CsrfService($this->state);$csrf->token();$cookie=new SessionCookie($config);$this->authentication=new FakeBrowserAuthentication();$this->sessions=new FakeBrowserSessions();
  $views=dirname(__DIR__,2).'/resources/views';$summaries=new DashboardSummaryService(new FakeDashboardSummaryStore());$homes=new DashboardHomeService($summaries,new UniversalToolProvider(),new CorporateToolsProvider(),new CorporateAccessService([]));$employees=new EmployeeWorkspaceController(new EmployeeDirectoryCriteriaFactory(),new EmployeeDirectoryService(new FakeFeatureEmployeeDirectoryStore()),new CorporateAccessService([]),new CorporateToolsProvider(),$csrf,$views);$this->kernel=new WebKernel(new AuthenticationController($this->authentication,$this->sessions,$cookie,$csrf,$views),new DashboardController($csrf,$homes,$views),new RequireAuthenticationMiddleware($this->sessions,$cookie),$employees);
 }
 public function testGetLoginRendersCsrfAndFields():void{$r=$this->kernel->handle(new Request('GET','/login'),$this->now());self::assertSame(200,$r->status);self::assertStringContainsString($this->state['csrf'],$r->body);self::assertStringContainsString('name="password"',$r->body);}
 public function testFailedLoginIsNeutralAndNeverEchoesPassword():void{$this->authentication->fail=true;$r=$this->kernel->handle($this->loginRequest('TEST-secret-password'),$this->now());self::assertSame(200,$r->status);self::assertStringContainsString('We could not sign you in',$r->body);self::assertStringNotContainsString('TEST-secret-password',$r->body);self::assertSame([],$r->cookies);}
 public function testSuccessfulLoginRedirectsAndSetsOpaqueCookie():void{$r=$this->kernel->handle($this->loginRequest('TEST-valid-password'),$this->now());self::assertSame(303,$r->status);self::assertSame('/dashboard',$r->headers['Location']);self::assertSame(str_repeat('s',43),$r->cookies[0]['value']);self::assertArrayNotHasKey('user_id',$r->cookies[0]);}
 public function testDashboardRequiresValidSession():void{$missing=$this->kernel->handle(new Request('GET','/dashboard'),$this->now());self::assertSame('/login',$missing->headers['Location']);$valid=$this->kernel->handle(new Request('GET','/dashboard',[],['npm_gateway_session'=>str_repeat('s',43)]),$this->now());self::assertSame(200,$valid->status);self::assertStringContainsString('Test Administrator',$valid->body);}
 public function testInvalidSessionClearsCookie():void{$this->sessions->invalid=true;$r=$this->kernel->handle(new Request('GET','/dashboard',[],['npm_gateway_session'=>str_repeat('s',43)]),$this->now());self::assertSame('/login',$r->headers['Location']);self::assertSame('',$r->cookies[0]['value']);}
 public function testLogoutIsPostOnlyAndRevokes():void{$get=$this->kernel->handle(new Request('GET','/logout'),$this->now());self::assertSame(405,$get->status);$post=$this->kernel->handle(new Request('POST','/logout',['_token'=>$this->state['csrf']],['npm_gateway_session'=>str_repeat('s',43)]),$this->now());self::assertSame('/login',$post->headers['Location']);self::assertTrue($this->sessions->loggedOut);self::assertSame('',$post->cookies[0]['value']);}
 public function testInvalidCsrfDoesNotAuthenticateOrLogout():void{$r=$this->kernel->handle(new Request('POST','/login',['_token'=>'bad','username'=>'admin','password'=>'TEST-secret']),$this->now());self::assertSame(419,$r->status);self::assertSame(0,$this->authentication->calls);}
 public function testEmployeeWorkspaceRoutesRequireAuthenticationAndRenderReadOnlyViews():void
 {
  $guest=$this->kernel->handle(new Request('GET','/employees'),$this->now());self::assertSame('/login',$guest->headers['Location']);
  $cookies=['npm_gateway_session'=>str_repeat('s',43)];$index=$this->kernel->handle(new Request('GET','/employees',[],$cookies,[],['search'=>'Test']),$this->now());self::assertSame(200,$index->status);self::assertStringContainsString('Employee Workspace',$index->body);
  $show=$this->kernel->handle(new Request('GET','/employees/'.str_repeat('A',26),[],$cookies),$this->now());self::assertSame(200,$show->status);self::assertStringContainsString('Test Employee',$show->body);
  self::assertSame(404,$this->kernel->handle(new Request('GET','/employees/17',[],$cookies),$this->now())->status);
 }
 private function loginRequest(string $password):Request{return new Request('POST','/login',['_token'=>$this->state['csrf'],'username'=>'admin','password'=>$password],[],['REMOTE_ADDR'=>'192.0.2.1']);}
 private function now():DateTimeImmutable{return new DateTimeImmutable('2026-07-28 10:00:00');}
}
final class FakeBrowserAuthentication implements AuthenticationServiceInterface
{
 public bool $fail=false;public int $calls=0;
 public function authenticate(LoginRequest $request,ClientContext $context):AuthenticationResult{$this->calls++;if($this->fail)throw new InvalidCredentialsException('Neutral');return new AuthenticationResult(FakeBrowserSessions::user(),new SessionToken(str_repeat('s',43),str_repeat('P',26)));}
}
final class FakeBrowserSessions implements SessionServiceInterface
{
 public bool $invalid=false;public bool $loggedOut=false;
 public static function user():AuthenticatedUser{return new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'admin','Test Administrator','Gateway Administrator');}
 public function validate(string $raw,ClientContext $context):SessionValidationResult{if($this->invalid)throw new InvalidSessionException('Invalid');return new SessionValidationResult(self::user());}
 public function logout(string $raw,ClientContext $context):void{$this->loggedOut=true;}
}
final class FakeDashboardSummaryStore implements DashboardSummaryStoreInterface
{
 public function counts():array{return ['property_count'=>0,'employee_count'=>1,'user_count'=>1,'active_user_count'=>1,'active_assignment_count'=>0];}
}
final class FakeFeatureEmployeeDirectoryStore implements EmployeeDirectoryStoreInterface
{
 public function searchDirectory(EmployeeDirectoryCriteria $criteria):array{return [['employee_public_id'=>str_repeat('A',26),'employee_number'=>'NPM000001','display_name'=>'Test Employee','job_title'=>'Tester','employee_class'=>'manager','employment_status'=>'active','business_email'=>null,'company_phone'=>null,'primary_property_name'=>'Not assigned','gateway_access_status'=>'Active']];}
 public function countDirectoryResults(EmployeeDirectoryCriteria $criteria):int{return 1;}
 public function findDirectoryProfileByPublicId(string $publicId):?EmployeeDirectoryProfile{return $publicId===str_repeat('A',26)?new EmployeeDirectoryProfile($publicId,'NPM000001','Test Employee','Tester','manager','active',null,null,'Active',[]):null;}
}
