<?php
declare(strict_types=1);
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\AuthenticationController;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Http\Request;
use NpmGateway\Http\SessionCookie;
use NpmGateway\Security\AuthenticationHasher;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\AuthenticationService;
use NpmGateway\Services\SessionService;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Support\SecureSessionTokenGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\LoginRequest;
use NpmGateway\ValueObjects\SessionToken;
use PHPUnit\Framework\TestCase;
final class AuthenticationServicesTest extends TestCase
{
 private function config(bool $secure=false):AuthenticationConfig{return new AuthenticationConfig('npm_gateway_session',$secure,true,'Lax',60,8,15,5,5,15,10,10,str_repeat('K',32));}
 public function testConfigurationAndKeyedHashes():void
 {
  $c=$this->config();$h=new AuthenticationHasher($c);self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/',$h->session('raw'));self::assertNotSame(hash('sha256','raw'),$h->session('raw'));self::assertNotSame($h->session('value'),$h->ip('value'));
 }
 public function testInvalidConfigurationRejected():void
 {
  $this->expectException(InvalidArgumentException::class);new AuthenticationConfig('bad cookie',false,true,'None',1,1,1,1,0,0,0,0,'short');
 }
 public function testTokenHasEntropyAndDoesNotSerialize():void
 {
  $g=new SecureSessionTokenGenerator();$a=$g->generate();$b=$g->generate();self::assertSame(43,strlen($a));self::assertNotSame($a,$b);$token=new SessionToken($a,str_repeat('A',26));self::assertStringNotContainsString($a,json_encode($token,JSON_THROW_ON_ERROR));self::assertFalse(method_exists($token,'__toString'));
 }
 public function testLoginRequestDoesNotSerializePassword():void
 {
  $request=new LoginRequest('admin','TEST-password-secret');self::assertStringNotContainsString('TEST-password-secret',json_encode($request,JSON_THROW_ON_ERROR));
 }
 public function testCookieAttributesAndMalformedIdentityFreeValue():void
 {
  $cookie=new SessionCookie($this->config());$set=$cookie->set(str_repeat('a',43));self::assertTrue($set['httponly']);self::assertFalse($set['secure']);self::assertSame('Lax',$set['samesite']);self::assertSame('/',$set['path']);self::assertArrayNotHasKey('user_id',$set);self::assertSame(1,$cookie->clear()['expires']);
 }
 public function testNativeAndGatewaySessionCookieNamesAreDistinct():void
 {
  $previous=$_ENV['NATIVE_SESSION_COOKIE_NAME']??null;$_ENV['NATIVE_SESSION_COOKIE_NAME']='npm_gateway_ui_state';
  $native=require dirname(__DIR__,2).'/config/session.php';
  if($previous===null)unset($_ENV['NATIVE_SESSION_COOKIE_NAME']);else $_ENV['NATIVE_SESSION_COOKIE_NAME']=$previous;
  self::assertSame('npm_gateway_ui_state',$native['name']);
  self::assertNotSame($this->config()->cookieName,$native['name']);
  $front=(string)file_get_contents(dirname(__DIR__,2).'/public/index.php');
  self::assertStringContainsString('session_name($nativeSessionName)',$front);
  self::assertStringContainsString('hash_equals($authConfig->cookieName,$nativeSessionName)',$front);
  self::assertStringNotContainsString("['SESSION_NAME']", $front);
 }
 public function testLoginViewHasRequiredSafeControls():void
 {
  $auth=(new ReflectionClass(AuthenticationService::class))->newInstanceWithoutConstructor();$sessions=(new ReflectionClass(SessionService::class))->newInstanceWithoutConstructor();$state=[];$csrf=new CsrfService($state);$controller=new AuthenticationController($auth,$sessions,new SessionCookie($this->config()),$csrf,dirname(__DIR__,2).'/resources/views');$response=$controller->loginForm();
  self::assertStringContainsString('autocomplete="username"',$response->body);self::assertStringContainsString('autocomplete="current-password"',$response->body);self::assertStringNotContainsStringIgnoringCase('Remember Me',$response->body);self::assertStringNotContainsStringIgnoringCase('Forgot Password',$response->body);
 }
 public function testDashboardDisplaysOnlyApprovedIdentity():void
 {
  $state=[];$store=new class implements DashboardSummaryStoreInterface{public function counts():array{return ['property_count'=>0,'employee_count'=>1,'user_count'=>1,'active_user_count'=>1,'active_assignment_count'=>0];}};$controller=new DashboardController(new CsrfService($state),new \NpmGateway\Services\DashboardHomeService(new DashboardSummaryService($store),new \NpmGateway\Services\UniversalToolProvider(),new \NpmGateway\Services\CorporateToolsProvider(),new \NpmGateway\Services\CorporateAccessService([])),dirname(__DIR__,2).'/resources/views');$user=new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'admin','Test Admin','Administrator','corporate');$response=$controller->index(new AuthenticatedRequestContext($user,str_repeat('x',43)));self::assertStringContainsString('Test Admin',$response->body);self::assertStringContainsString('@admin · Administrator',$response->body);self::assertStringContainsString('Welcome, Test Admin.',$response->body);self::assertStringNotContainsString(str_repeat('x',43),$response->body);self::assertStringContainsString('method="post" action="/logout"',$response->body);
 }
 public function testArchitectureBoundariesAndNoMigration003():void
 {
  $root=dirname(__DIR__,2);$sql='/\\b(?:SELECT\\s|INSERT\\s+INTO|UPDATE\\s+[A-Za-z_][A-Za-z0-9_]*\\s+SET|DELETE\\s+FROM)\\b/i';foreach(glob($root.'/app/Http/Controllers/*.php')?:[] as $f){$s=file_get_contents($f);self::assertDoesNotMatchRegularExpression($sql,$s);self::assertStringNotContainsString('Repositories\\',$s);self::assertStringNotContainsString('password_verify',$s);}
  foreach(glob($root.'/app/Http/Middleware/*.php')?:[] as $f){$s=file_get_contents($f);self::assertDoesNotMatchRegularExpression($sql,$s);self::assertStringNotContainsString('Repositories\\',$s);}
  foreach(glob($root.'/app/Repositories/*.php')?:[] as $f){$s=file_get_contents($f);self::assertStringNotContainsString('begin_transaction',$s);self::assertStringNotContainsString('Services\\',$s);}
  self::assertFileDoesNotExist($root.'/database/migrations/202607270003_authentication.php');self::assertStringNotContainsString("'GET' => '/logout'",file_get_contents($root.'/routes/web.php'));
 }
}
