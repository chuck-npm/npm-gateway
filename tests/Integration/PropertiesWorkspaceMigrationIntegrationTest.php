<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/DisposableMigrationBoundary.php';
final class PropertiesWorkspaceMigrationIntegrationTest extends TestCase
{
 public function testMigrationAppliesAndRollsBackOnlyOnDisposableDatabase():void{if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true.');$app=require dirname(__DIR__,2).'/bootstrap/app.php';$config=DatabaseProfiles::load('migration',$app['root']);$db=MySqlConnectionFactory::connect($config);$boundary=new DisposableMigrationBoundary($db,$app['root']);try{$boundary->atBoundary('202607310003_properties_workspace',function()use($db,$app):void{$this->assertColumns($db,true);$migration=require $app['root'].'/database/migrations/202607310003_properties_workspace.php';$migration->down($db);$this->assertColumns($db,false);$migration->up($db);$this->assertColumns($db,true);});}finally{$db->close();}}
 private function assertColumns(mysqli $db,bool $present):void{foreach(['prop_id','office_phone','ivr_routing_email'] as $column){$s=$db->prepare("SELECT COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='properties' AND COLUMN_NAME=?");$s->bind_param('s',$column);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();self::assertSame($present,is_array($row));if($present&&$column==='prop_id')self::assertSame('int unsigned',$row['COLUMN_TYPE']);}foreach(['active_primary_manager_property_id','active_primary_manager_employee_id'] as $column){$s=$db->prepare("SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='employee_property_assignments' AND COLUMN_NAME=?");$s->bind_param('s',$column);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();self::assertSame($present,is_array($row));}}
}
