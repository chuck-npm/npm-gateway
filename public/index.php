<?php
declare(strict_types=1);
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Container\ServiceProvider;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Http\Controllers\AuthenticationController;
use NpmGateway\Http\Controllers\DashboardController;
use NpmGateway\Http\Middleware\RequireAuthenticationMiddleware;
use NpmGateway\Http\Request;
use NpmGateway\Http\SessionCookie;
use NpmGateway\Http\WebKernel;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\AuthenticationService;
use NpmGateway\Services\SessionService;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Http\Controllers\EmployeeWorkspaceController;
use NpmGateway\Services\EmployeeDirectoryCriteriaFactory;
use NpmGateway\Services\EmployeeDirectoryService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\Controllers\AdminController;
use NpmGateway\Services\CategoryAccessAdministrationService;
use NpmGateway\Services\CategoryAccessPayloadParser;
use NpmGateway\Http\Controllers\PropertyWorkspaceController;
use NpmGateway\Http\Controllers\HumanResourcesController;
use NpmGateway\Services\PropertyAdministrationService;
use NpmGateway\Services\PropertyDirectoryCriteriaFactory;
use NpmGateway\Services\PropertyQueryService;
use NpmGateway\Support\FlashSession;
use NpmGateway\Http\Controllers\HrEmployeeController;
use NpmGateway\Services\HrEmployeeCreationService;

$application=require dirname(__DIR__).'/bootstrap/app.php';$root=$application['root'];$environment=(string)$application['config']['app']['environment'];
$path=parse_url((string)($_SERVER['REQUEST_URI']??'/'),PHP_URL_PATH);$path=is_string($path)?rtrim($path,'/'):'/';$path=$path===''?'/':$path;
if($path==='/component-showcase'&&in_array($environment,['local','development'],true)){
 $responseStatus=200;$pageTitle='Component Showcase — Development Only';$navbarItems=[];$navbarUserLabel='User menu';ob_start();require $root.'/resources/views/pages/component-showcase.php';$contentHtml=(string)ob_get_clean();require $root.'/resources/views/layouts/app.php';return;
}
if($path==='/component-showcase'){
 $responseStatus=404;http_response_code(404);require $root.'/resources/views/errors/404.php';return;
}
$container=ServiceProvider::build($application);$authConfig=$container->get(AuthenticationConfig::class);
$nativeSession=require $root.'/config/session.php';$nativeSessionName=(string)$nativeSession['name'];
if(!preg_match('/^[A-Za-z][A-Za-z0-9_-]{1,63}$/',$nativeSessionName)||hash_equals($authConfig->cookieName,$nativeSessionName)){throw new RuntimeException('Native PHP and Gateway authentication cookie names must be valid and distinct.');}
if(session_status()!==PHP_SESSION_ACTIVE){ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');session_name($nativeSessionName);session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$authConfig->secure,'httponly'=>true,'samesite'=>$authConfig->sameSite]);session_start();}
$csrf=new CsrfService($_SESSION);$cookie=new SessionCookie($authConfig);$views=$root.'/resources/views';
$authentication=new AuthenticationController($container->get(AuthenticationService::class),$container->get(SessionService::class),$cookie,$csrf,$views);
$employees=new EmployeeWorkspaceController($container->get(EmployeeDirectoryCriteriaFactory::class),$container->get(EmployeeDirectoryService::class),$container->get(CorporateAccessService::class),$container->get(CorporateToolsProviderInterface::class),$csrf,$views);
$properties=new PropertyWorkspaceController($container->get(PropertyDirectoryCriteriaFactory::class),$container->get(PropertyQueryService::class),$container->get(PropertyAdministrationService::class),$container->get(CorporateAccessService::class),$container->get(CorporateToolsProviderInterface::class),$csrf,new FlashSession($_SESSION),$views);
$hr=new HumanResourcesController($container->get(CorporateAccessService::class),$container->get(CorporateToolsProviderInterface::class),$csrf,$views);
$hrEmployees=new HrEmployeeController($container->get(EmployeeDirectoryCriteriaFactory::class),$container->get(EmployeeDirectoryService::class),$container->get(HrEmployeeCreationService::class),$container->get(CorporateAccessService::class),$container->get(CorporateToolsProviderInterface::class),$csrf,new FlashSession($_SESSION),$views);
$admin=new AdminController($container->get(CorporateAccessService::class),$container->get(CategoryAccessAdministrationService::class),$container->get(CategoryAccessPayloadParser::class),$container->get(CorporateToolsProviderInterface::class),$csrf,new FlashSession($_SESSION),$views);
$kernel=new WebKernel($authentication,new DashboardController($csrf,$container->get(DashboardHomeService::class),$views),new RequireAuthenticationMiddleware($container->get(SessionService::class),$cookie),$employees,$properties,$hr,$hrEmployees,$admin);
$request=new Request(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET')),$path,$_POST,array_map('strval',$_COOKIE),array_map('strval',$_SERVER),array_map('strval',$_GET));
$response=$kernel->handle($request,$container->get(ClockInterface::class)->now());http_response_code($response->status);
foreach($response->headers as $name=>$value)header($name.': '.$value);
foreach($response->cookies as $definition){$name=$definition['name'];$value=$definition['value'];unset($definition['name'],$definition['value']);setcookie($name,$value,$definition);}
header('Content-Type: text/html; charset=UTF-8');echo $response->body;
$container->get(mysqli::class)->close();
