<?php
declare(strict_types=1);
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Database\MySqlInitializationTransaction;
use NpmGateway\Notifications\CompanyAnnouncementEmailRenderer;
use NpmGateway\Notifications\CompanyNoticeEmailSender;
use NpmGateway\Repositories\AuditRepository;
use NpmGateway\Repositories\NotificationRecipientRepository;
use NpmGateway\Repositories\NotificationRepository;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\CompanyNoticePublicationService;
use NpmGateway\Services\CompanyNoticeReviewStore;
use NpmGateway\Services\CompanyNoticeValidator;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\Support\CompanyDateFormatter;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;
final class CompanyNoticePublicationIntegrationTest extends TestCase
{
 private mysqli $db;private PublicIdGenerator $ids;
 protected function setUp():void{if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true with both profiles on npmgateway_test.');$app=require dirname(__DIR__,2).'/bootstrap/app.php';foreach(['application','migration'] as $profile)self::assertSame('npmgateway_test',DatabaseProfiles::load($profile,$app['root'])['database']);$this->db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$app['root']));$this->ids=new PublicIdGenerator();$this->clean();}
 protected function tearDown():void{if(isset($this->db)){$this->clean();$this->db->close();}}
 public function testComposeReviewBackEditReviewPublishIsSingleAndIdempotent():void
 {
  $actor=$this->person('Publisher','One','publisher','publisher@example.test');$this->person('Recipient','Two','recipient2','recipient2@example.test');$this->person('Recipient','Three','recipient3','recipient3@example.test');$mails=[];$service=$this->service($mails);$session=[];$store=new CompanyNoticeReviewStore($session,$this->ids,static fn():int=>1000);$first=$store->create($actor->id,['title'=>'TEST Notice','message'=>'Original sentence.','priority'=>'normal','requires_acknowledgment'=>true]);$edit=$store->get($first,$actor->id)['data'];self::assertSame('Original sentence.',$edit['message']);$edit['message']='Changed sentence.';$edit['priority']='urgent';$edit['requires_acknowledgment']=false;$second=$store->create($actor->id,$edit);self::assertSame('superseded',$store->resolve($first,$actor->id)['status']);self::assertSame('created',$store->resolve($second,$actor->id)['status']);$review=$store->get($second,$actor->id);$publication=$service->publish($review,$actor);$store->complete($second,$publication['public_id']);$dispatch=$service->dispatch($publication,$actor);self::assertFalse($dispatch['reporting_failed']);self::assertSame(3,$dispatch['sent']);self::assertCount(3,$mails);self::assertSame(1,$this->scalar("SELECT COUNT(*) FROM notifications WHERE notification_type='company_notice'"));self::assertSame(3,$this->scalar('SELECT COUNT(*) FROM notification_recipients'));self::assertSame(1,$this->scalar("SELECT COUNT(*) FROM audit_logs WHERE event_type='company_notice.published'"));$retry=$service->publish($review,$actor);self::assertTrue($retry['already_published']);$service->dispatch($retry,$actor);self::assertCount(3,$mails);self::assertSame(1,$this->scalar("SELECT COUNT(*) FROM notifications WHERE notification_type='company_notice'"));
 }
 public function testTransactionFailureLeavesNoRowsAndSendsNoEmail():void
 {
  $this->person('Recipient','One','recipient','recipient@example.test');$mails=[];$service=$this->service($mails);$invalidActor=new AuthenticatedUser(999999,999999,$this->ids->generate(),$this->ids->generate(),'missing','Missing Actor');$review=['data'=>['title'=>'TEST Failure','message'=>'Must roll back.','priority'=>'normal','requires_acknowledgment'=>true],'source'=>$this->ids->generate()];try{$service->publish($review,$invalidActor);self::fail('Foreign-key failure was not raised.');}catch(mysqli_sql_exception){self::addToAssertionCount(1);}self::assertSame(0,$this->scalar("SELECT COUNT(*) FROM notifications WHERE notification_type='company_notice'"));self::assertSame(0,$this->scalar('SELECT COUNT(*) FROM notification_recipients'));self::assertSame(0,$this->scalar("SELECT COUNT(*) FROM audit_logs WHERE event_type='company_notice.published'"));self::assertCount(0,$mails);
 }
 private function service(array &$mails):CompanyNoticePublicationService
 {
  $config=new HrEmployeeNotificationConfig('smtp.example.test',587,'test','test','tls','no-reply@npmpropertiesinc.com','NPM Gateway',[],'testing');$factory=static function()use(&$mails):PHPMailer{$mail=new class(true) extends PHPMailer{public function send():bool{return true;}};$mails[]=$mail;return $mail;};$clock=new class implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-02 10:00:00');}};return new CompanyNoticePublicationService(new NotificationRepository($this->db),new NotificationRecipientRepository($this->db),new MySqlInitializationTransaction($this->db),$this->ids,$clock,new CompanyNoticeValidator(),new CompanyNoticeEmailSender($config,new CompanyAnnouncementEmailRenderer(),$factory),new AuditService(new AuditRepository($this->db),$this->ids),new CompanyDateFormatter());
 }
 private function person(string $first,string $last,string $username,string $email):AuthenticatedUser{$employeePublic=$this->ids->generate();$userPublic=$this->ids->generate();$number='NPM'.str_pad((string)($this->scalar('SELECT COUNT(*) FROM employees')+1),6,'0',STR_PAD_LEFT);$s=$this->db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,business_email,job_title,employment_status,start_date) VALUES(?,?, 'corporate',?,?,?,'Test Role','active','2026-08-02')");$s->bind_param('sssss',$employeePublic,$number,$first,$last,$email);$s->execute();$employeeId=$this->db->insert_id;$s->close();$hash=password_hash('Test-password-123!',PASSWORD_DEFAULT);$s=$this->db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES(?,?,?,?,'active')");$s->bind_param('siss',$userPublic,$employeeId,$username,$hash);$s->execute();$userId=$this->db->insert_id;$s->close();return new AuthenticatedUser($userId,$employeeId,$userPublic,$employeePublic,$username,$first.' '.$last);}
 private function scalar(string $sql):int{return (int)$this->db->query($sql)->fetch_row()[0];}
 private function clean():void{foreach(['notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table)$this->db->query("DELETE FROM {$table}");}
}
