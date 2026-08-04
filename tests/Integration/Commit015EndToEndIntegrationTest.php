<?php
declare(strict_types=1);
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Container\ServiceProvider;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\StorageController;
use NpmGateway\Notifications\CompanyAnnouncementEmailRenderer;
use NpmGateway\Notifications\CompanyNoticeEmailSender;
use NpmGateway\Repositories\CategoryAccessRepository;
use NpmGateway\Repositories\NotificationStorageObjectRepository;
use NpmGateway\Services\CompanyNoticeAssetService;
use NpmGateway\Services\CompanyNoticeComposeStore;
use NpmGateway\Services\CompanyNoticePublicationService;
use NpmGateway\Services\CompanyNoticeReviewStore;
use NpmGateway\Services\CompanyNoticeValidator;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\Services\PublishedStorageService;
use NpmGateway\Services\StorageConfiguration;
use NpmGateway\Services\TemporaryStorageCleanupService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;
final class Commit015EndToEndIntegrationTest extends TestCase
{
 private ?mysqli $db=null;private ?StorageAdapterInterface $adapter=null;private ?StorageConfiguration $config=null;private array $paths=[];private array $mails=[];private ?object $container=null;
 protected function setUp():void{if(getenv('RUN_COMMIT015_E2E')!=='true')self::markTestSkipped('Guarded Commit 015 E2E is not enabled.');$app=require dirname(__DIR__,2).'/bootstrap/app.php';foreach(['application','migration'] as $profile)self::assertSame('npmgateway_test',DatabaseProfiles::load($profile,$app['root'])['database']);self::assertSame('company_notices/test/',getenv('WASABI_TEST_PREFIX'));self::assertStringStartsWith('company_notices/test/',(string)getenv('WASABI_COMPANY_NOTICE_ATTACHMENTS_PREFIX'));self::assertStringStartsWith('company_notices/test/',(string)getenv('WASABI_COMPANY_NOTICE_IMAGES_PREFIX'));$_SESSION=[];$this->container=ServiceProvider::build($app);$this->db=$this->container->get(mysqli::class);$this->adapter=$this->container->get(StorageAdapterInterface::class);$this->config=$this->container->get(StorageConfiguration::class);self::assertSame([],$this->adapter->listPrefix($this->config->container,'company_notices/test/'));$this->cleanDb();}
 protected function tearDown():void
 {
  $failure=null;
  try{
   if($this->adapter!==null&&$this->config!==null){foreach($this->adapter->listPrefix($this->config->container,'company_notices/test/') as $key)$this->adapter->delete($this->config->container,$key);self::assertSame([],$this->adapter->listPrefix($this->config->container,'company_notices/test/'));}
  }catch(\Throwable $e){$failure=$e;}
  try{if($this->db!==null)$this->cleanDb();}catch(\Throwable $e){$failure??=$e;}
  finally{
   foreach($this->paths as $path)if(is_file($path)&&!unlink($path))$failure??=new \RuntimeException('A disposable upload fixture could not be removed.');
   if($this->db!==null){try{$this->db->close();}catch(\Throwable $e){$failure??=$e;}}
   $this->db=null;$this->adapter=null;$this->config=null;$this->container=null;
  }
  if($failure!==null)throw $failure;
 }
 public function testCompleteComposePublishDownloadEmailAndCleanupFlow():void
 {
  $publisher=$this->person('Publisher','Tester','publisher015','publisher@example.test');$recipientA=$this->person('Recipient','One','recipient015a','recipient-a@example.test');$recipientB=$this->person('Recipient','Two','recipient015b','recipient-b@example.test');$this->grant($publisher);
  $ids=$this->container->get(PublicIdGenerator::class);$compose=$this->container->get(CompanyNoticeComposeStore::class)->active($publisher->id);$assets=$this->container->get(CompanyNoticeAssetService::class);
  $pdf=$this->file('Policy.pdf',"%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n");$zip=$this->zip();$png=$this->file('Entrance.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',true));
  $pdfAsset=$assets->upload($compose['id'],$pdf,'attachment',$publisher);$zipAsset=$assets->upload($compose['id'],$zip,'attachment',$publisher);$imageAsset=$assets->upload($compose['id'],$png,'embedded_image',$publisher);foreach([$pdfAsset,$zipAsset,$imageAsset] as $asset){self::assertArrayNotHasKey('object_key',$asset);self::assertArrayNotHasKey('provider',$asset);$row=$this->object($asset['public_id']);self::assertSame('temporary',$row['lifecycle_state']);self::assertSame($publisher->id,(int)$row['temporary_review_owner_user_id']);self::assertStringStartsWith('company_notices/test/',$row['object_key']);self::assertTrue($this->adapter->exists($this->config->container,$row['object_key']));}
  $raw='<p>First <strong>bold</strong> and <em>italic</em>.</p><ul><li>Bullet</li></ul><ol><li>Number</li></ol><p><a href="https://example.test/policy">Policy site</a></p><p><img data-storage-object-public-id="'.$imageAsset['public_id'].'" src="gateway-storage:'.$imageAsset['public_id'].'" alt="Property entrance"></p>';$validator=$this->container->get(CompanyNoticeValidator::class);$data=$validator->validate(['title'=>'Commit 015 disposable notice','message'=>'fallback','rich_message_html'=>$raw,'priority'=>'important','requires_acknowledgment'=>'yes','compose_context'=>$compose['id']]);self::assertStringNotContainsString('style=',$data['rich_message_html']);
  $reviews=new CompanyNoticeReviewStore($_SESSION,$ids,static fn():int=>1000);$first=$reviews->create($publisher->id,$data);$back=$reviews->get($first,$publisher->id)['data'];self::assertSame($data['rich_message_html'],$back['rich_message_html']);self::assertCount(3,$assets->authorized($compose['id'],$publisher));$second=$reviews->create($publisher->id,$back);self::assertSame('superseded',$reviews->resolve($first,$publisher->id)['status']);
  $config=new HrEmployeeNotificationConfig('smtp.example.test',587,'fake','fake','tls','no-reply@example.test','NPM Gateway',[],'testing');$factory=function():PHPMailer{$mail=new class(true) extends PHPMailer{public function send():bool{return true;}};$this->mails[]=$mail;return $mail;};$sender=new CompanyNoticeEmailSender($config,new CompanyAnnouncementEmailRenderer(),$factory,$this->adapter,'https://gateway.example.test');$app=require dirname(__DIR__,2).'/bootstrap/app.php';$container=ServiceProvider::build($app);$container->instance(CompanyNoticeEmailSender::class,$sender);$service=$container->get(CompanyNoticePublicationService::class);$review=$reviews->get($second,$publisher->id);$publication=$service->publish($review,$publisher);$reviews->complete($second,$publication['public_id']);$dispatch=$service->dispatch($publication,$publisher);self::assertSame(3,$dispatch['sent']);self::assertSame(1,$this->rowCount("notifications WHERE notification_type='company_notice'"));self::assertSame(3,$this->rowCount('notification_recipients'));self::assertSame(3,$this->rowCount('notification_storage_objects'));self::assertSame(3,$this->rowCount("storage_objects WHERE lifecycle_state='published' AND temporary_review_owner_user_id IS NULL AND published_at IS NOT NULL"));$retry=$service->publish($review,$publisher);self::assertTrue($retry['already_published']??false);$service->dispatch($retry,$publisher);self::assertCount(3,$this->mails);
  foreach($this->mails as $mail){self::assertStringContainsString('Policy.pdf',$mail->Body);self::assertStringContainsString('Archive.zip',$mail->Body);self::assertStringContainsString('https://gateway.example.test/storage/'.$pdfAsset['public_id'],$mail->Body);self::assertStringContainsString('cid:gateway-'.strtolower($imageAsset['public_id']),$mail->Body);self::assertStringNotContainsString('wasabisys.com',$mail->Body);self::assertStringNotContainsString('company_notices/test/',$mail->Body);self::assertStringContainsString('Policy.pdf',$mail->AltBody);self::assertStringNotContainsString('<p>',$mail->AltBody);self::assertStringNotContainsString('gateway-storage:',$mail->AltBody);self::assertCount(1,$mail->getAttachments());}
  $unassigned=$this->person('Unassigned','User','unassigned015','unassigned@example.test');$published=$this->container->get(PublishedStorageService::class);$recipientContext=new AuthenticatedRequestContext($recipientA,'token');$publisherContext=new AuthenticatedRequestContext($publisher,'token');$deniedContext=new AuthenticatedRequestContext($unassigned,'token');self::assertNotNull($published->authorized($pdfAsset['public_id'],$recipientContext,'attachment'));self::assertNotNull($published->authorized($pdfAsset['public_id'],$publisherContext,'attachment'));self::assertNull($published->authorized($pdfAsset['public_id'],$deniedContext,'attachment'));$response=(new StorageController($published))->download($pdfAsset['public_id'],false,$recipientContext);self::assertSame(200,$response->status);self::assertSame('nosniff',$response->headers['X-Content-Type-Options']);self::assertSame(hash_file('sha256',$pdf['tmp_name']),hash('sha256',stream_get_contents($response->stream)));$imageResponse=(new StorageController($published))->download($imageAsset['public_id'],true,$recipientContext);self::assertSame(200,$imageResponse->status);self::assertStringStartsWith('inline',$imageResponse->headers['Content-Disposition']);
  $old=$assets->upload($compose['id'],$this->file('Old.pdf',"%PDF-1.4\n%%EOF\n"),'attachment',$publisher);$recent=$assets->upload($compose['id'],$this->file('Recent.pdf',"%PDF-1.4\n%%EOF\n"),'attachment',$publisher);$this->db->query("UPDATE storage_objects SET created_at='2026-07-01 00:00:00' WHERE public_id='".$this->db->real_escape_string($old['public_id'])."'");$result=$this->container->get(TemporaryStorageCleanupService::class)->run(new DateTimeImmutable('2026-08-02 12:00:00'),24);self::assertGreaterThanOrEqual(1,$result['deleted']);self::assertSame('deleted',$this->object($old['public_id'])['lifecycle_state']);self::assertNull($this->object($old['public_id'])['deleted_by_user_id']);self::assertSame('temporary',$this->object($recent['public_id'])['lifecycle_state']);
 }
 private function person(string $first,string $last,string $username,string $email):AuthenticatedUser{$ids=$this->container->get(PublicIdGenerator::class);$employeePublic=$ids->generate();$userPublic=$ids->generate();$number='NPM'.random_int(100000,999999);$s=$this->db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,business_email,job_title,employment_status,start_date) VALUES(?,?,'corporate',?,?,?,'Test','active','2026-08-02')");$s->bind_param('sssss',$employeePublic,$number,$first,$last,$email);$s->execute();$employee=$this->db->insert_id;$s->close();$hash=password_hash('Disposable-123!',PASSWORD_DEFAULT);$s=$this->db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES(?,?,?,?,'active')");$s->bind_param('siss',$userPublic,$employee,$username,$hash);$s->execute();$user=$this->db->insert_id;$s->close();return new AuthenticatedUser($user,$employee,$userPublic,$employeePublic,$username,$first.' '.$last);}
 private function grant(AuthenticatedUser $user):void{(new CategoryAccessRepository($this->db))->grant(['public_id'=>$this->container->get(PublicIdGenerator::class)->generate(),'user_id'=>$user->id,'category'=>'company-notices','granted_by_user_id'=>$user->id,'granted_at'=>'2026-08-02 10:00:00']);}
 private function file(string $name,string $contents):array{$path=tempnam(sys_get_temp_dir(),'npm015-');file_put_contents($path,$contents);$this->paths[]=$path;return ['name'=>$name,'tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];}
 private function zip():array{$path=tempnam(sys_get_temp_dir(),'npm015-');$zip=new ZipArchive();$zip->open($path,ZipArchive::OVERWRITE);$zip->addFromString('readme.txt','Disposable archive');$zip->close();$this->paths[]=$path;return ['name'=>'Archive.zip','tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];}
 private function object(string $publicId):array{$s=$this->db->prepare('SELECT * FROM storage_objects WHERE public_id=?');$s->bind_param('s',$publicId);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();return $row;}
 private function rowCount(string $expression):int{return (int)$this->db->query('SELECT COUNT(*) FROM '.$expression)->fetch_row()[0];}
 private function cleanDb():void{foreach(['notification_storage_objects','storage_objects','notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table)$this->db->query("DELETE FROM {$table}");}
}
