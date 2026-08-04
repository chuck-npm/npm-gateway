<?php
declare(strict_types=1);
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\CategoryAccessAdministrationService;
use NpmGateway\Services\CategoryAccessBackfillService;
use NpmGateway\Services\CategoryAccessPayloadParser;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;

final class CategoryAccessManagementTest extends TestCase
{
    private array $categories=['operations'=>'Operations','human-resources'=>'Human Resources','company-notices'=>'Company Notices','finance'=>'Finance','marketing'=>'Marketing','admin'=>'Admin','credit-cards'=>'Credit Cards'];

    public function testMigrationContainsApprovedSchemaAndGuardedRollback():void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/database/migrations/202608010007_user_category_access.php');
        foreach(['CREATE TABLE user_category_access','UNIQUE KEY uq_user_category_access_user_category','ON DELETE RESTRICT','CHECK (category IN','utf8mb4_0900_ai_ci','COUNT(*) FROM user_category_access'] as $expected)self::assertStringContainsString($expected,$source);
        self::assertSame(3,substr_count($source,'FOREIGN KEY'));
    }

    public function testConfigurationContainsDefinitionsButNoPermanentUsernames():void
    {
        $config=require dirname(__DIR__,2).'/config/corporate-access.php';self::assertSame(['categories'],array_keys($config));self::assertSame($this->categories,$config['categories']);self::assertStringNotContainsString('chuck',strtolower(var_export($config,true)));self::assertStringNotContainsString('tim',strtolower(var_export($config,true)));
    }

    public function testAccessUsesUserIdMembershipAndDeniesUnknownCategory():void
    {
        $store=new CategoryAccessMemoryStore();$store->effective[7]['finance']=true;$service=new CorporateAccessService($store,$this->categories);$context=new AuthenticatedRequestContext(new AuthenticatedUser(7,4,str_repeat('U',26),str_repeat('E',26),'renamed','Test User'),'token');
        self::assertTrue($service->canAccessCategory($context,' FINANCE '));self::assertFalse($service->canAccessCategory($context,'admin'));self::assertFalse($service->canAccessCategory($context,'unsupported'));
    }

    public function testBackfillCreatesExactApprovedMembershipsAndIsIdempotent():void
    {
        $store=$this->storeWithUsers(true);$audits=new CategoryAccessAuditStore();$service=new CategoryAccessBackfillService($store,new CategoryAccessTransaction(),new AuditService($audits,new PublicIdGenerator()),new PublicIdGenerator(),new CategoryAccessClock(),$this->categories);
        self::assertSame(14,$service->run()['created']);self::assertSame(0,$service->run()['created']);self::assertCount(14,$store->memberships());self::assertTrue($store->effective[1]['admin']);self::assertArrayNotHasKey('admin',$store->effective[2]);self::assertTrue($store->effective[3]['company-notices']);self::assertNotEmpty($audits->events);
    }

    public function testBackfillRefusesMissingRequiredUserWithoutWriting():void
    {
        $store=$this->storeWithUsers(true);$store->users=array_slice($store->users,0,1);$service=new CategoryAccessBackfillService($store,new CategoryAccessTransaction(),new AuditService(new CategoryAccessAuditStore(),new PublicIdGenerator()),new PublicIdGenerator(),new CategoryAccessClock(),$this->categories);
        $this->expectException(RuntimeException::class);try{$service->run();}finally{self::assertSame([],$store->memberships());}
    }

    public function testAdminCannotRemoveOwnAdminAccess():void
    {
        $store=$this->storeWithUsers();$store->effective[1]['admin']=true;$service=$this->administration($store);$actor=new AuthenticatedUser(1,11,str_repeat('C',26),str_repeat('E',26),'chuck','Chuck');
        $this->expectException(InvalidArgumentException::class);$this->expectExceptionMessage('cannot remove your own Admin');$service->applyChanges(['users'=>[str_repeat('C',26),str_repeat('T',26)],'access'=>[str_repeat('C',26)=>[],str_repeat('T',26)=>[]]],$actor);
    }

    public function testAdminMatrixUsesOnlySafeDisplayFields():void
    {
        $matrix=$this->administration($this->storeWithUsers())->matrix();self::assertSame(['public_id','username','status','display_name','categories'],array_keys($matrix['users'][0]));self::assertArrayNotHasKey('password_hash',$matrix['users'][0]);
    }

    public function testRealPhpFormShapeParsesTimAdminAndAbsentCheckboxesAsFalse():void
    {
        $chuck=str_repeat('C',26);$tim=str_repeat('T',26);parse_str('users%5B%5D='.$chuck.'&users%5B%5D='.$tim.'&access%5B'.$chuck.'%5D%5Bfinance%5D=1&access%5B'.$chuck.'%5D%5Badmin%5D=1&access%5B'.$tim.'%5D%5Badmin%5D=1',$post);$parsed=(new CategoryAccessPayloadParser($this->categories))->parse($post);
        self::assertSame([$chuck,$tim],$parsed['users']);self::assertTrue($parsed['access'][$tim]['admin']);self::assertArrayNotHasKey('finance',$parsed['access'][$tim]);self::assertSame([],array_diff(array_keys($parsed['access'][$chuck]),['finance','admin']));
    }

    public function testRenderedFormAndFrontControllerPreserveNestedPayloadContract():void
    {
        $root=dirname(__DIR__,2);$view=(string)file_get_contents($root.'/resources/views/admin/category-access.php');$front=(string)file_get_contents($root.'/public/index.php');$controller=(string)file_get_contents($root.'/app/Http/Controllers/AdminController.php');
        self::assertStringContainsString('name="users[]"',$view);self::assertStringContainsString('name="access[<?= $escape((string)$row[\'public_id\']) ?>][<?= $escape((string)$category) ?>]"',$view);self::assertStringContainsString('value="1"',$view);self::assertStringNotContainsString("row['id']",$view);self::assertStringContainsString('$path,$_POST,',$front);self::assertStringNotContainsString("array_map('strval',\$_POST)",$front);self::assertStringContainsString('$this->csrf->valid',$controller);self::assertStringContainsString('$this->payloads->parse($request->post)',$controller);
    }

    public function testParserRejectsMalformedNestedPayloads():void
    {
        $id=str_repeat('C',26);$parser=new CategoryAccessPayloadParser($this->categories);
        foreach([
            ['access'=>[$id=>['admin'=>'1']]],
            ['users'=>[$id,$id],'access'=>[]],
            ['users'=>['bad'],'access'=>[]],
            ['users'=>[$id],'access'=>[$id=>['unknown'=>'1']]],
            ['users'=>[$id],'access'=>[$id=>['admin'=>['1']]]],
            ['users'=>[$id],'access'=>[$id=>['admin'=>'yes']]],
            ['users'=>[$id],'access'=>[str_repeat('T',26)=>['admin'=>'1']]],
        ] as $payload){try{$parser->parse($payload);self::fail('Malformed payload was accepted.');}catch(InvalidArgumentException){self::addToAssertionCount(1);}}
    }

    public function testGrantingTimAdminCreatesOnlyOneMembershipAndRealAudits():void
    {
        $store=$this->storeWithUsers();foreach(array_keys($this->categories) as $category)$store->effective[1][$category]=true;foreach(['operations','finance','human-resources','company-notices','marketing','credit-cards'] as $category)$store->effective[2][$category]=true;$audits=new CategoryAccessAuditStore();$service=new CategoryAccessAdministrationService($store,new CategoryAccessTransaction(),new AuditService($audits,new PublicIdGenerator()),new PublicIdGenerator(),new CategoryAccessClock(),$this->categories);$actor=new AuthenticatedUser(1,11,str_repeat('C',26),str_repeat('E',26),'chuck','Chuck');$access=[str_repeat('C',26)=>array_fill_keys(array_keys($this->categories),true),str_repeat('T',26)=>array_fill_keys(array_keys($this->categories),true)];
        self::assertSame(1,$service->applyChanges(['users'=>[str_repeat('C',26),str_repeat('T',26)],'access'=>$access],$actor));self::assertCount(14,$store->memberships());self::assertTrue($store->effective[2]['admin']);self::assertSame(['admin.category_access_granted','admin.category_access_updated'],array_column($audits->events,'event_type'));self::assertSame(0,$service->applyChanges(['users'=>[str_repeat('C',26),str_repeat('T',26)],'access'=>$access],$actor));self::assertCount(2,$audits->events);
    }

    public function testServiceRejectsOmittedOrUnknownUsersBeforeMutation():void
    {
        $store=$this->storeWithUsers();$store->effective[1]['admin']=true;$service=$this->administration($store);$actor=new AuthenticatedUser(1,11,str_repeat('C',26),str_repeat('E',26),'chuck','Chuck');$before=$store->memberships();
        foreach([[str_repeat('C',26)],[str_repeat('C',26),str_repeat('X',26)]] as $users){try{$service->applyChanges(['users'=>$users,'access'=>[]],$actor);self::fail('Tampered rows were accepted.');}catch(InvalidArgumentException){self::assertSame($before,$store->memberships());}}
    }

    private function administration(CategoryAccessMemoryStore $store):CategoryAccessAdministrationService{return new CategoryAccessAdministrationService($store,new CategoryAccessTransaction(),new AuditService(new CategoryAccessAuditStore(),new PublicIdGenerator()),new PublicIdGenerator(),new CategoryAccessClock(),$this->categories);}
    private function storeWithUsers(bool $includeHayleigh=false):CategoryAccessMemoryStore{$store=new CategoryAccessMemoryStore();$store->users=[['id'=>1,'public_id'=>str_repeat('C',26),'employee_id'=>11,'username'=>'chuck','status'=>'active','display_name'=>'Chuck Admin'],['id'=>2,'public_id'=>str_repeat('T',26),'employee_id'=>22,'username'=>'tim','status'=>'active','display_name'=>'Tim User']];if($includeHayleigh)$store->users[]=['id'=>3,'public_id'=>str_repeat('H',26),'employee_id'=>33,'username'=>'hayleigh','status'=>'active','display_name'=>'Hayleigh Owens'];return $store;}
}

final class CategoryAccessMemoryStore implements CategoryAccessStoreInterface
{
    public array $users=[];public array $effective=[];
    public function hasEffectiveMembership(int $userId,string $category):bool{return isset($this->effective[$userId][$category]);}
    public function findUserByUsername(string $username):?array{$matches=array_values(array_filter($this->users,static fn(array $u):bool=>$u['username']===$username));return count($matches)===1?$matches[0]:null;}
    public function allUsers():array{return $this->users;}
    public function memberships():array{$rows=[];foreach($this->effective as $id=>$categories)foreach(array_keys($categories) as $category)$rows[]=['user_id'=>$id,'category'=>$category];return $rows;}
    public function grant(array $membership):void{$this->effective[(int)$membership['user_id']][(string)$membership['category']]=true;}
    public function revoke(int $userId,string $category):void{unset($this->effective[$userId][$category]);}
}
final class CategoryAccessTransaction implements InitializationTransactionInterface{public function acquire(string $lockName,int $timeoutSeconds):bool{return true;}public function begin():void{}public function commit():void{}public function rollback():void{}public function release(string $lockName):void{}}
final class CategoryAccessClock implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-01 12:00:00');}}
final class CategoryAccessAuditStore implements AuditStoreInterface{public array $events=[];public function insert(array $event):void{$this->events[]=$event;}}
