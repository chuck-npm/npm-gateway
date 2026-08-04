<?php
declare(strict_types=1);
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Repositories\AuditRepository;
use NpmGateway\Repositories\StorageObjectRepository;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\CompanyNoticeAssetService;
use NpmGateway\Services\CompanyNoticeComposeStore;
use NpmGateway\Services\CompanyNoticeDraftDiscardService;
use NpmGateway\Services\CompanyNoticeReviewStore;
use NpmGateway\Services\GatewayStorageService;
use NpmGateway\Services\TemporaryStorageDeletionService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\StorageProviderHead;
use NpmGateway\ValueObjects\StorageProviderObject;
use PHPUnit\Framework\TestCase;
final class CompanyNoticeDraftDiscardIntegrationTest extends TestCase
{
 private mysqli $db;
 protected function setUp():void{if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Database integration tests are disabled.');$app=require dirname(__DIR__,2).'/bootstrap/app.php';self::assertSame('npmgateway_test',DatabaseProfiles::load('application',$app['root'])['database']);$this->db=MySqlConnectionFactory::connect(DatabaseProfiles::load('application',$app['root']));$this->clean();}
 protected function tearDown():void{if(isset($this->db)){$this->clean();$this->db->close();}}
 public function testAttachmentAndImageAreDeletedAndDraftStateAndReviewsAreInvalidated():void
 {
  [$actor,$service,$compose,$reviews,$adapter]=$this->fixture(false);$attachment=$this->object($actor,'attachment-key');$image=$this->object($actor,'image-key');$compose->select($compose->current($actor->id)['id'],$actor->id,$attachment,'attachment');$compose->select($compose->current($actor->id)['id'],$actor->id,$image,'embedded_image');$context=$compose->current($actor->id)['id'];$token=$reviews->create($actor->id,['compose_context'=>$context,'title'=>'Draft']);$result=$service->discard($context,$actor);self::assertSame('discarded',$result['status']);self::assertSame(1,$result['attachment_count']);self::assertSame(1,$result['embedded_image_count']);self::assertNull($compose->current($actor->id));self::assertSame('unavailable',$reviews->resolve($token,$actor->id)['status']);self::assertSame(2,(int)$this->db->query("SELECT COUNT(*) FROM storage_objects WHERE lifecycle_state='deleted' AND temporary_review_owner_user_id IS NULL")->fetch_row()[0]);self::assertSame(0,count($adapter->objects));self::assertSame(1,(int)$this->db->query("SELECT COUNT(*) FROM audit_logs WHERE event_type='company_notice.draft_discarded'")->fetch_row()[0]);self::assertSame('unavailable',$service->discard($context,$actor)['status']);self::assertNotSame($context,$compose->active($actor->id)['id']);
 }
 public function testStorageRemovalFailureKeepsComposeReviewAndTemporaryObject():void
 {
  [$actor,$service,$compose,$reviews]=$this->fixture(true);$asset=$this->object($actor,'fail-key');$context=$compose->current($actor->id)['id'];$compose->select($context,$actor->id,$asset,'attachment');$token=$reviews->create($actor->id,['compose_context'=>$context]);$result=$service->discard($context,$actor);self::assertSame('failed',$result['status']);self::assertSame($context,$compose->current($actor->id)['id']);self::assertSame('created',$reviews->resolve($token,$actor->id)['status']);self::assertSame(1,(int)$this->db->query("SELECT COUNT(*) FROM storage_objects WHERE lifecycle_state='temporary'")->fetch_row()[0]);self::assertSame(0,(int)$this->db->query("SELECT COUNT(*) FROM audit_logs WHERE event_type='company_notice.draft_discarded'")->fetch_row()[0]);
 }
 private function fixture(bool $fail):array
 {
  $ids=new PublicIdGenerator();$employee=$ids->generate();$userPublic=$ids->generate();$this->db->query("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES ('$employee','NPM916016','corporate','Draft','Tester','Tester','active','2026-08-03')");$employeeId=$this->db->insert_id;$hash=password_hash('test',PASSWORD_DEFAULT);$s=$this->db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES (?,?,'draft016',?,'active')");$s->bind_param('sis',$userPublic,$employeeId,$hash);$s->execute();$userId=$this->db->insert_id;$s->close();$actor=new AuthenticatedUser($userId,$employeeId,$userPublic,$employee,'draft016','Draft Tester');$session=[];$compose=new CompanyNoticeComposeStore($session,$ids,static fn():int=>1000);$compose->active($userId);$reviews=new CompanyNoticeReviewStore($session,$ids,static fn():int=>1000);$objects=new StorageObjectRepository($this->db);$adapter=new DraftDiscardStorageAdapter($fail?'fail-key':null);$audit=new AuditService(new AuditRepository($this->db),$ids);$deletion=new TemporaryStorageDeletionService($objects,$adapter,$audit);$gateway=(new ReflectionClass(GatewayStorageService::class))->newInstanceWithoutConstructor();$assets=new CompanyNoticeAssetService($compose,$objects,$gateway,$deletion);$clock=new class implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-03 18:00:00');}};return [$actor,new CompanyNoticeDraftDiscardService($compose,$assets,$reviews,$audit,$clock),$compose,$reviews,$adapter];
 }
 private function object(AuthenticatedUser $actor,string $key):string{$public=(new PublicIdGenerator())->generate();$sha=str_repeat('a',64);$ownerId=$actor->id;$s=$this->db->prepare("INSERT INTO storage_objects(public_id,provider,provider_container,object_key,original_filename,display_filename,mime_type,byte_size,sha256_hex,lifecycle_state,uploaded_by_user_id,temporary_review_owner_user_id,created_at) VALUES (?,'wasabi','test-bucket',?,'fixture.txt','fixture.txt','text/plain',1,?,'temporary',?,?,'2026-08-03 18:00:00')");$s->bind_param('sssii',$public,$key,$sha,$ownerId,$ownerId);$s->execute();$s->close();return $public;}
 private function clean():void{foreach(['notification_storage_objects','storage_objects','notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table)$this->db->query("DELETE FROM $table");}
}
final class DraftDiscardStorageAdapter implements StorageAdapterInterface
{
 public array $objects=[];public function __construct(private readonly ?string $failure){}
 public function put(string $container,string $objectKey,mixed $stream,int $byteSize,string $mimeType,string $sha256):StorageProviderObject{throw new LogicException();}
 public function openReadStream(string $container,string $objectKey):mixed{throw new LogicException();}
 public function exists(string $container,string $objectKey):bool{return isset($this->objects[$objectKey]);}
 public function delete(string $container,string $objectKey):void{if($objectKey===$this->failure)throw new RuntimeException('simulated');unset($this->objects[$objectKey]);}
 public function head(string $container,string $objectKey):StorageProviderHead{throw new LogicException();}
 public function listPrefix(string $container,string $prefix):array{return [];}
}
