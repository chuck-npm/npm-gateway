<?php
declare(strict_types=1);
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\CompanyNoticeController;
use NpmGateway\Http\Request;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\NotificationCount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class NotificationCreateNoticeActionTest extends TestCase
{
 #[DataProvider('accessCases')]
 public function testCreateActionUsesOnlyEffectiveCompanyNoticesAccess(array $effective,string $class,bool $expected):void
 {
  $access=$this->access($effective);$context=$this->context($class);$canCreateNotice=$access->canAccessCategory($context,'company-notices');self::assertSame($expected,$canCreateNotice);$html=$this->render($canCreateNotice,[]);
  if($expected){self::assertStringContainsString('<a class="btn btn-primary" href="/company-notices/create">Create Notice</a>',$html);}else{self::assertStringNotContainsString('Create Notice',$html);self::assertStringNotContainsString('/company-notices/create',$html);self::assertStringNotContainsString('<header class="mb-4 d-flex justify-content-between align-items-start"><div><h1>Notifications</h1><p>Review company notices and required communications.</p></div><a',$html);}
 }
 public static function accessCases():array{return ['corporate class alone'=>[[],'corporate',false],'admin alone'=>[['admin'],'corporate',false],'company notices granted'=>[['company-notices'],'maintenance',true],'disabled or inactive membership'=>[[],'manager',false]];}
 public function testEmptyAndPopulatedListsRemainUnchangedWithoutAction():void{$empty=$this->render(false,[]);self::assertStringContainsString('You have no notices requiring acknowledgment.',$empty);$populated=$this->render(false,[['public_id'=>str_repeat('N',26),'title'=>'Required Notice','summary'=>'Read this notice.','priority'=>'normal','published_at'=>'2026-08-04 12:00:00','acknowledged_at'=>null]]);self::assertStringContainsString('Required Notice',$populated);self::assertStringContainsString('Read this notice.',$populated);self::assertStringNotContainsString('/company-notices/create',$populated);}
 public function testUnauthorizedDirectCreateRequestRemainsDenied():void{$reflection=new ReflectionClass(CompanyNoticeController::class);$controller=$reflection->newInstanceWithoutConstructor();$reflection->getProperty('access')->setValue($controller,$this->access([]));$response=$controller->create(new Request('GET','/company-notices/create'),$this->context('corporate'));self::assertSame(403,$response->status);self::assertSame('Forbidden',$response->body);}
 public function testControllerAndRouteShareAuthoritativeProtectionAndMarkupAddsNoInlineAssets():void{$root=dirname(__DIR__,2);$controller=(string)file_get_contents($root.'/app/Http/Controllers/NotificationController.php');self::assertStringContainsString("canAccessCategory(\$context,'company-notices')",$controller);self::assertStringNotContainsString("item->key==='company-notices'",$controller);$company=(string)file_get_contents($root.'/app/Http/Controllers/CompanyNoticeController.php');self::assertStringContainsString("canAccessCategory(\$c,'company-notices')",$company);$routes=require $root.'/routes/web.php';self::assertContains('company-notices-access',$routes['/company-notices/create']['middleware']);self::assertSame(['GET'],$routes['/company-notices/create']['methods']);$view=(string)file_get_contents($root.'/resources/views/notifications/index.php');self::assertStringNotContainsString('<script',$view);self::assertStringNotContainsString('style=',$view);}
 private function access(array $effective):CorporateAccessService{$store=new class($effective) implements CategoryAccessStoreInterface{public function __construct(private array $effective){}public function hasEffectiveMembership(int $userId,string $category):bool{return in_array($category,$this->effective,true);}public function findUserByUsername(string $username):?array{return null;}public function allUsers():array{return [];}public function memberships():array{return [];}public function grant(array $membership):void{}public function revoke(int $userId,string $category):void{}};$config=require dirname(__DIR__,2).'/config/corporate-access.php';return new CorporateAccessService($store,$config['categories']);}
 private function context(string $class):AuthenticatedRequestContext{return new AuthenticatedRequestContext(new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'user','Test User','Administrator',$class),'token');}
 private function render(bool $canCreateNotice,array $notices):string{$root=dirname(__DIR__,2);$user=$this->context('corporate')->user;$notificationCount=new NotificationCount(0);$navbarCorporateItems=[];$logoutCsrfToken='csrf';$csrfToken='csrf';$success='';$filter='outstanding';ob_start();require $root.'/resources/views/notifications/index.php';return (string)ob_get_clean();}
}
