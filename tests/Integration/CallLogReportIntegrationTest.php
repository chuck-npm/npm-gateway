<?php
declare(strict_types=1);
use NpmGateway\Database\{DatabaseProfiles,MySqlConnectionFactory};
use NpmGateway\Repositories\CallLogReportRepository;
use NpmGateway\Services\{CallLogReportDateRangeFactory,CallLogReportService};
use PHPUnit\Framework\TestCase;
final class CallLogReportIntegrationTest extends TestCase
{
 private mysqli$db;
 protected function setUp():void{$root=dirname(__DIR__,2);require$root.'/bootstrap/app.php';$this->db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$root));if($this->db->query('SELECT DATABASE()')->fetch_row()[0]!=='npmgateway_test')self::markTestSkipped('Requires npmgateway_test.');$this->db->begin_transaction();}
 protected function tearDown():void{if(isset($this->db)){$this->db->rollback();$this->db->close();}}
 public function testThresholdDatesAttributionRosterAndCompanyAggregation():void
 {
  $existing=$this->db->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetch_row();if($existing===null){$this->db->query("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES('01TESTREPORTEMPLOYEE000001X','NPM999998','corporate','Report','Fixture','Tester','active','2026-01-01')");$employee=(int)$this->db->insert_id;$this->db->query("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES('01TESTREPORTUSER000000001X',{$employee},'reportfixture','fixture','active')");}$user=(int)($existing[0]??$this->db->insert_id);$destinations=[];foreach($this->db->query("SELECT d.id,COALESCE(p.display_name,d.external_display_name) name FROM call_log_destinations d LEFT JOIN properties p ON p.id=d.property_id")->fetch_all(MYSQLI_ASSOC)as$row)$destinations[$row['name']]=(int)$row['id'];
  $public='01TESTCALLREPORTIMPORT001X';$hash=hash('sha256',$public);$file='call-report-fixture.xls';$at='2026-08-15 00:00:00';$s=$this->db->prepare("INSERT INTO call_log_imports(public_id,original_filename,file_sha256,uploaded_by_user_id,source_row_count,imported_row_count,source_started_at,source_ended_at,imported_at,created_at) VALUES(?,?,?, ?,10,10,'2026-01-01 00:00:00.000','2026-01-08 00:00:00.000',?,?)");$s->bind_param('sssiss',$public,$file,$hash,$user,$at,$at);$s->execute();$import=(int)$this->db->insert_id;$s->close();
  $rows=[
   ['Pine Hill','2026-01-01 00:00:00.000','0.031'],['Pine Hill','2026-01-01 10:00:00.000','2.437'],['Pine Hill','2026-01-07 23:59:59.999','34.999'],['Pine Hill','2026-01-07 12:00:00.000','35.000'],['Pine Hill','2026-01-03 12:00:00.000','35.001'],['Pine Hill','2026-01-04 12:00:00.000','111.262'],
   ['Highridge','2026-01-02 12:00:00.000','35.000'],['Highridge','2026-01-02 13:00:00.000','1.000'],['Suburban','2026-01-03 12:00:00.000','100.000'],['Pine Hill','2026-01-08 00:00:00.000','100.000']];
  foreach($rows as$i=>[$name,$started,$duration]){$id=str_pad('TESTCALLREPORT'.($i+1),26,'X');$released=$started;$calling='+12295550101';$called='+12295550102';$destination=$destinations[$name];$s=$this->db->prepare('INSERT INTO call_logs(public_id,import_id,destination_id,calling_tn,called_tn,started_at,released_at,call_duration_seconds,created_at) VALUES(?,?,?,?,?,?,?,?,?)');$s->bind_param('siissssss',$id,$import,$destination,$calling,$called,$started,$released,$duration,$at);$s->execute();$s->close();}
  $report=(new CallLogReportService(new CallLogReportRepository($this->db),new CallLogReportDateRangeFactory()))->facebookPerformance(['from'=>'2026-01-01','to'=>'2026-01-07']);$byName=[];foreach($report['rows']as$row)$byName[$row['property_name']]=$row;
  self::assertSame(['Boulder Trails','Crumley Farms','Flamingo Flats','Highridge','Maplewind','Pearce Pointe','Pine Hill','Pine Manor','Sizemore','Wunderpark'],array_column($report['rows'],'property_name'));self::assertArrayNotHasKey('Suburban',$byName);
  foreach(['Pine Manor','Sizemore']as$name)self::assertSame([0,0,0,null],[$byName[$name]['total_calls'],$byName[$name]['no_answer'],$byName[$name]['answered'],$byName[$name]['percent_answered']]);
  self::assertSame([6,3,3,50],[$byName['Pine Hill']['total_calls'],$byName['Pine Hill']['no_answer'],$byName['Pine Hill']['answered'],$byName['Pine Hill']['percent_answered']]);self::assertSame([2,1,1,50],[$byName['Highridge']['total_calls'],$byName['Highridge']['no_answer'],$byName['Highridge']['answered'],$byName['Highridge']['percent_answered']]);self::assertSame(['total_calls'=>8,'no_answer'=>4,'answered'=>4,'percent_answered'=>50],$report['totals']);self::assertSame($report['totals']['total_calls'],$report['totals']['no_answer']+$report['totals']['answered']);foreach($report['rows']as$row)self::assertSame($row['total_calls'],$row['no_answer']+$row['answered']);
 }
}
