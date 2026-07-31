<?php
declare(strict_types=1);
use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\TestCase;
final class PropertiesWorkspaceMigrationIntegrationTest extends TestCase
{
    public function testMigrationAppliesAndRollsBackOnlyOnDisposableDatabase():void
    {
        if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true with npmgateway_test profiles.');$application=require dirname(__DIR__,2).'/bootstrap/app.php';$migration=DatabaseProfiles::load('migration',$application['root']);$app=DatabaseProfiles::load('application',$application['root']);self::assertSame('npmgateway_test',$migration['database']);self::assertSame('npmgateway_test',$app['database']);self::assertContains($migration['host'],['127.0.0.1','localhost','::1']);$directory=$application['root'].'/database/migrations';MigrationCommand::execute('migrate',$migration,$directory);$this->assertColumns($migration,true);$rollback=implode("\n",MigrationCommand::execute('migrate:rollback',$migration,$directory));self::assertStringContainsString('202607310003_properties_workspace',$rollback);$this->assertColumns($migration,false);MigrationCommand::execute('migrate',$migration,$directory);$this->assertColumns($migration,true);
    }
    private function assertColumns(array $config,bool $present):void{$connection=MySqlConnectionFactory::connect($config);try{foreach(['prop_id','office_phone','ivr_routing_email'] as $column){$s=$connection->prepare('SELECT COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=\'properties\' AND COLUMN_NAME=?');$database=(string)$config['database'];$s->bind_param('ss',$database,$column);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();self::assertSame($present,is_array($row));if($present){self::assertSame('YES',$row['IS_NULLABLE']);if($column==='prop_id')self::assertSame('int unsigned',$row['COLUMN_TYPE']);}}foreach(['active_primary_manager_property_id','active_primary_manager_employee_id'] as $column){$s=$connection->prepare('SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=\'employee_property_assignments\' AND COLUMN_NAME=?');$database=(string)$config['database'];$s->bind_param('ss',$database,$column);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();self::assertSame($present,is_array($row));if($present)self::assertStringContainsString('STORED GENERATED',strtoupper((string)$row['EXTRA']));}}finally{$connection->close();}}
}
