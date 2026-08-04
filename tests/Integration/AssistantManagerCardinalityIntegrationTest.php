<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Repositories\EmployeeRepository;
use NpmGateway\Repositories\PropertyRepository;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
use NpmGateway\ValueObjects\PropertyDirectoryCriteria;
use PHPUnit\Framework\TestCase;
final class AssistantManagerCardinalityIntegrationTest extends TestCase
{
 private mysqli $db;private PublicIdGenerator $ids;
 protected function setUp():void{if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true with both profiles on npmgateway_test.');$app=require dirname(__DIR__,2).'/bootstrap/app.php';foreach(['application','migration'] as $profile)self::assertSame('npmgateway_test',DatabaseProfiles::load($profile,$app['root'])['database']);$this->db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$app['root']));$this->ids=new PublicIdGenerator();$this->clean();}
 protected function tearDown():void{if(isset($this->db)){$this->clean();$this->db->close();}}
 public function testManagerAndMultipleAssistantManagersCoexistWithoutChangingDirectoryManager():void
 {
  $property=$this->property();$mary=$this->employee('Mary','Poppins','NPM900001');$harry=$this->employee('Harry','Potter','NPM900002');$hermione=$this->employee('Hermione','Granger','NPM900003');$secondManager=$this->employee('Second','Manager','NPM900004');
  $this->assignment($mary,$property,'property_manager');$this->assignment($harry,$property,'assistant_manager');$this->assignment($hermione,$property,'assistant_manager');
  self::assertSame(3,(int)$this->db->query('SELECT COUNT(*) FROM employee_property_assignments')->fetch_row()[0]);$generated=$this->db->query("SELECT assignment_type,active_primary_manager_property_id,active_primary_manager_employee_id FROM employee_property_assignments ORDER BY id")->fetch_all(MYSQLI_ASSOC);self::assertSame((string)$property,(string)$generated[0]['active_primary_manager_property_id']);self::assertNull($generated[1]['active_primary_manager_property_id']);self::assertNull($generated[1]['active_primary_manager_employee_id']);self::assertNull($generated[2]['active_primary_manager_property_id']);
  try{$this->assignment($secondManager,$property,'property_manager');self::fail('Second active primary Property Manager accepted.');}catch(mysqli_sql_exception $e){self::assertSame(1062,$e->getCode());}
  $properties=(new PropertyRepository($this->db))->searchDirectory(new PropertyDirectoryCriteria());self::assertSame('Mary Poppins',$properties[0]['manager_name']);$employees=(new EmployeeRepository($this->db))->searchDirectory(new EmployeeDirectoryCriteria('Harry Potter'));self::assertCount(1,$employees);self::assertSame('Cove Haven',$employees[0]['primary_property_name']);self::assertSame('manager',$employees[0]['employee_class']);
 }
 private function property():int{$id=$this->ids->generate();$s=$this->db->prepare("INSERT INTO properties(public_id,prop_id,property_code,slug,display_name,status,manager_email,address_line_1,city,state,postal_code,timezone) VALUES(?,44,'CH','cove-haven','Cove Haven','active','manager@covehaven.test','1 Cove Way','Columbus','GA','31904','America/New_York')");$s->bind_param('s',$id);$s->execute();$pk=$this->db->insert_id;$s->close();return $pk;}
 private function employee(string $first,string $last,string $number):int{$id=$this->ids->generate();$email=strtolower($first).'.'.strtolower($last).'@example.test';$s=$this->db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,business_email,company_phone,job_title,employment_status,start_date) VALUES(?,?, 'manager',?,?,?,'+15551234567','Manager','active','2026-08-02')");$s->bind_param('sssss',$id,$number,$first,$last,$email);$s->execute();$pk=$this->db->insert_id;$s->close();return $pk;}
 private function assignment(int $employee,int $property,string $type):void{$id=$this->ids->generate();$s=$this->db->prepare("INSERT INTO employee_property_assignments(public_id,employee_id,property_id,assignment_type,is_primary,starts_on) VALUES(?,?,?,?,1,'2026-08-02')");$s->bind_param('siis',$id,$employee,$property,$type);$s->execute();$s->close();}
 private function clean():void{foreach(['notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table)$this->db->query("DELETE FROM {$table}");}
}
