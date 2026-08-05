<?php
declare(strict_types=1);
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Exceptions\Domain\ProtectedPrincipalViolationException;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\ProtectedPrincipalService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;
final class ProtectedPrincipalTest extends TestCase
{
    private array $categories=['admin'=>'Admin','finance'=>'Finance'];
    public function testConfigurationIsStrictAndNeverUsesMutableIdentity():void
    {
        $user=str_repeat('A',26);$employee=str_repeat('E',26);$config=ProtectedPrincipalConfig::fromArray(['user_public_id'=>$user,'employee_public_id'=>$employee,'required_categories'=>'admin'],$this->categories);self::assertSame($user,$config->userPublicId);self::assertSame(['admin'],$config->requiredCategories);
        foreach([['user_public_id'=>'Chuck Lundquist','employee_public_id'=>$employee],['user_public_id'=>$user,'employee_public_id'=>''],['user_public_id'=>$user,'employee_public_id'=>$employee,'required_categories'=>'unknown']] as $bad){try{ProtectedPrincipalConfig::fromArray($bad,$this->categories);self::fail('Unsafe configuration accepted.');}catch(InvalidArgumentException){self::addToAssertionCount(1);}}
    }
    public function testOnlyConfiguredPublicIdsAreProtected():void
    {
        [$service]=$this->service();self::assertTrue($service->isProtectedUser(str_repeat('A',26)));self::assertFalse($service->isProtectedUser(str_repeat('C',26)));self::assertFalse($service->isProtectedUser('Chuck Lundquist'));self::assertTrue($service->isProtectedEmployee(str_repeat('E',26)));
    }
    public function testHealthDetectsStatusLinkAndMembership():void
    {
        [$service,$store]=$this->service();self::assertTrue($service->health()['healthy']);unset($store->members[1]['admin']);self::assertContains('missing_category:admin',$service->health()['reasons']);$store->members[1]['admin']=true;$store->users[0]['status']='disabled';self::assertContains('user_inactive',$service->health()['reasons']);
    }
    public function testProtectedCategoryRevocationIsDeniedAndSanitizedAuditCreated():void
    {
        [$service,,$audits]=$this->service();$actor=new AuthenticatedUser(2,22,str_repeat('T',26),str_repeat('F',26),'admin2','Admin Two');try{$service->assertCategoryBaseline(['public_id'=>str_repeat('A',26)],[],$actor);self::fail('Revocation accepted.');}catch(ProtectedPrincipalViolationException $e){self::assertSame('This protected Gateway administrator cannot be deactivated or removed.',$e->getMessage());}self::assertSame('security.protected_category_revocation_denied',$audits->events[0]['event_type']);$json=json_encode($audits->events[0]);foreach(['password','session_token','personal_email'] as $secret)self::assertStringNotContainsString($secret,$json);
    }
    public function testIdentityMutationGuardsApplyToUserAndEmployee():void
    {
        [$service]=$this->service();foreach([fn()=>$service->assertUserMutation(str_repeat('A',26),'deactivate'),fn()=>$service->assertEmployeeMutation(str_repeat('E',26),'delete')] as $operation){try{$operation();self::fail('Protected mutation accepted.');}catch(ProtectedPrincipalViolationException){self::addToAssertionCount(1);}}$service->assertUserMutation(str_repeat('T',26),'deactivate');self::addToAssertionCount(1);
    }
    public function testAdminViewContainsAccessibleLockedBaselineAndNoPostedProtectionFlag():void
    {
        $view=(string)file_get_contents(dirname(__DIR__,2).'/resources/views/admin/category-access.php');foreach(['Protected Administrator','Required for Gateway continuity','disabled aria-describedby="protected-continuity-note"'] as $text)self::assertStringContainsString($text,$view);foreach(['name="protected"','name="is_owner"','name="super_admin"'] as $unsafe)self::assertStringNotContainsString($unsafe,$view);
    }
    public function testCliCommandsAreExplicitAndRepairUsesMigrationProfile():void
    {
        $cli=(string)file_get_contents(dirname(__DIR__,2).'/bin/gateway');foreach(['protected-principal:check','protected-principal:repair --confirm',"DatabaseProfiles::load('migration'",'Proposed protected-principal repairs'] as $required)self::assertStringContainsString($required,$cli);self::assertStringNotContainsString("protected-principal:repair'&&\$_SERVER",$cli);
    }
    private function service():array
    {
        $store=new ProtectedMemoryStore();$store->users=[['id'=>1,'public_id'=>str_repeat('A',26),'employee_id'=>11,'employee_public_id'=>str_repeat('E',26),'username'=>'chuck','status'=>'active','employment_status'=>'active','display_name'=>'Any Name']];$store->members=[1=>['admin'=>true,'finance'=>true]];$audits=new ProtectedAuditStore();$config=ProtectedPrincipalConfig::fromArray(['user_public_id'=>str_repeat('A',26),'employee_public_id'=>str_repeat('E',26),'required_categories'=>'admin'],$this->categories);return [new ProtectedPrincipalService($config,$store,new AuditService($audits,new PublicIdGenerator()),new ProtectedClock()),$store,$audits];
    }
}
final class ProtectedMemoryStore implements CategoryAccessStoreInterface{public array $users=[];public array $members=[];public function hasEffectiveMembership(int $userId,string $category):bool{return isset($this->members[$userId][$category]);}public function findUserByUsername(string $username):?array{return null;}public function allUsers():array{return $this->users;}public function memberships():array{$rows=[];foreach($this->members as $id=>$categories)foreach(array_keys($categories)as$category)$rows[]=['user_id'=>$id,'category'=>$category];return $rows;}public function grant(array $membership):void{$this->members[$membership['user_id']][$membership['category']]=true;}public function revoke(int $userId,string $category):void{unset($this->members[$userId][$category]);}}
final class ProtectedAuditStore implements AuditStoreInterface{public array $events=[];public function insert(array $event):void{$this->events[]=$event;}}
final class ProtectedClock implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-05 12:00:00');}}
