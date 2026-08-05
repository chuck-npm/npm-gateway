<?php
declare(strict_types=1);
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Database\MySqlInitializationTransaction;
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Repositories\AuditRepository;
use NpmGateway\Repositories\EmployeeEmergencyContactRepository;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\EmployeeEmergencyContactService;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;
final class EmployeeEmergencyContactIntegrationTest extends TestCase
{
 private mysqli $db;private PublicIdGenerator $ids;
 protected function setUp():void{if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true with both profiles on npmgateway_test.');$app=require dirname(__DIR__,2).'/bootstrap/app.php';foreach(['application','migration'] as $profile)self::assertSame('npmgateway_test',DatabaseProfiles::load($profile,$app['root'])['database']);$this->db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$app['root']));$this->ids=new PublicIdGenerator();$this->clean();}
 protected function tearDown():void{if(isset($this->db)){$this->clean();$this->db->close();}}
 public function testCreateUpdateOwnershipPrivacyAndMigrationLifecycle():void
 {
  $one=$this->person('Corporate','One','corporate1','corporate');$two=$this->person('Manager','Two','manager2','manager');$service=$this->service();self::assertNull($service->findFor($one));$created=$service->save($one,['first_name'=>'Jane','last_name'=>"O'Neil",'relationship'=>'Parent','primary_phone'=>'(570) 555-0101','alternate_phone'=>'']);self::assertSame('Jane',$created->firstName);self::assertSame('+15705550101',$created->primaryPhone);self::assertNull($created->alternatePhone);self::assertSame(1,$this->scalar('SELECT COUNT(*) FROM employee_emergency_contacts'));$public=$created->publicId;
  $updated=$service->save($one,['first_name'=>'Janet','last_name'=>"O'Neil",'relationship'=>'Parent','primary_phone'=>'570-555-0102','alternate_phone'=>'5705550103','employee_id'=>$two->employeeId]);self::assertSame($public,$updated->publicId);self::assertSame('+15705550103',$updated->alternatePhone);self::assertSame(1,$this->scalar('SELECT COUNT(*) FROM employee_emergency_contacts'));self::assertNull($service->findFor($two));
  self::assertSame(1,$this->scalar("SELECT COUNT(*) FROM audit_logs WHERE event_type='employee.emergency_contact_created'"));self::assertSame(1,$this->scalar("SELECT COUNT(*) FROM audit_logs WHERE event_type='employee.emergency_contact_updated'"));$audit=(string)$this->db->query("SELECT GROUP_CONCAT(after_data) FROM audit_logs WHERE event_type LIKE 'employee.emergency_contact_%'")->fetch_row()[0];foreach(['Jane','Janet',"O'Neil",'5705550101','5705550102','Parent'] as $private)self::assertStringNotContainsString($private,$audit);self::assertSame(0,$this->scalar('SELECT COUNT(*) FROM notifications'));self::assertSame(0,$this->scalar('SELECT COUNT(*) FROM notification_recipients'));
  foreach([['first_name'=>'','last_name'=>'Valid','relationship'=>'Friend','primary_phone'=>'5705550101'],['first_name'=>['bad'],'last_name'=>'Valid','relationship'=>'Friend','primary_phone'=>'5705550101'],['first_name'=>'<script>','last_name'=>'Valid','relationship'=>'Friend','primary_phone'=>'bad']] as $invalid){try{$service->save($one,$invalid);self::fail('Invalid contact accepted.');}catch(\NpmGateway\Exceptions\Domain\InvalidEmergencyContactException){self::addToAssertionCount(1);}}self::assertSame(1,$this->scalar('SELECT COUNT(*) FROM employee_emergency_contacts'));
  $migration=require dirname(__DIR__,2).'/database/migrations/202608040013_employee_emergency_contacts.php';try{$migration->down($this->db);self::fail('Rollback destroyed emergency contact data.');}catch(MigrationException){self::addToAssertionCount(1);}$this->db->query('DELETE FROM employee_emergency_contacts');$this->db->query("DELETE FROM audit_logs WHERE event_type LIKE 'employee.emergency_contact_%'");$migration->down($this->db);self::assertSame(0,$this->scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='employee_emergency_contacts'"));$migration->up($this->db);self::assertSame(1,$this->scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='employee_emergency_contacts'"));
 }
 private function service():EmployeeEmergencyContactService{$clock=new class implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-04 12:00:00');}};return new EmployeeEmergencyContactService(new EmployeeEmergencyContactRepository($this->db),new MySqlInitializationTransaction($this->db),$this->ids,$clock,new PhoneFormatter(),new AuditService(new AuditRepository($this->db),$this->ids));}
 private function person(string $first,string $last,string $username,string $class):AuthenticatedUser{$ep=$this->ids->generate();$up=$this->ids->generate();$number='NPM'.str_pad((string)($this->scalar('SELECT COUNT(*) FROM employees')+950000),6,'0',STR_PAD_LEFT);$s=$this->db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES(?,?,?,?,?,'Tester','active','2026-08-04')");$s->bind_param('sssss',$ep,$number,$class,$first,$last);$s->execute();$employee=$this->db->insert_id;$s->close();$hash=password_hash('test',PASSWORD_DEFAULT);$s=$this->db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES(?,?,?,?,'active')");$s->bind_param('siss',$up,$employee,$username,$hash);$s->execute();$user=$this->db->insert_id;$s->close();return new AuthenticatedUser($user,$employee,$up,$ep,$username,$first.' '.$last,'Tester',$class);}
 private function scalar(string $sql):int{return (int)$this->db->query($sql)->fetch_row()[0];}
 private function clean():void{foreach(['employee_emergency_contacts','notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table){$exists=$this->scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'");if($exists)$this->db->query("DELETE FROM {$table}");}}
}
