<?php
declare(strict_types=1);
use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\TestCase;
final class EmployeeDateOfBirthMigrationIntegrationTest extends TestCase
{
    public function testNullableColumnAndGuardedRollbackOnDisposableDatabase():void
    {
        if(getenv('RUN_DB_INTEGRATION_TESTS')!=='true')self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true with both profiles on npmgateway_test.');
        $application=require dirname(__DIR__,2).'/bootstrap/app.php';foreach(['application','migration'] as $profile)self::assertSame('npmgateway_test',DatabaseProfiles::load($profile,$application['root'])['database']);$config=DatabaseProfiles::load('migration',$application['root']);$directory=$application['root'].'/database/migrations';MigrationCommand::execute('migrate',$config,$directory);$migration=require $directory.'/202608010006_employee_date_of_birth.php';$db=MySqlConnectionFactory::connect($config);
        try{
            $column=$db->query("SELECT COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='npmgateway_test' AND TABLE_NAME='employees' AND COLUMN_NAME='date_of_birth'")->fetch_assoc();self::assertSame('date',$column['COLUMN_TYPE']);self::assertSame('YES',$column['IS_NULLABLE']);self::assertSame(0,(int)$db->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='npmgateway_test' AND TABLE_NAME='employees' AND COLUMN_NAME='date_of_birth'")->fetch_row()[0]);
            $db->query("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES ('DOBNULLINTEGRATIONTEST00001','NPM999995','corporate','Legacy','Employee','Test','active','2020-01-01')");self::assertNull($db->query("SELECT date_of_birth FROM employees WHERE employee_number='NPM999995'")->fetch_row()[0]);
            $migration->down($db);self::assertSame(0,(int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='npmgateway_test' AND TABLE_NAME='employees' AND COLUMN_NAME='date_of_birth'")->fetch_row()[0]);$migration->up($db);$db->query("UPDATE employees SET date_of_birth='1985-08-14' WHERE employee_number='NPM999995'");
            try{$migration->down($db);self::fail('Rollback discarded Date of Birth.');}catch(RuntimeException $exception){self::assertStringContainsString('destroy restricted employee data',$exception->getMessage());}
        }finally{$db->query("DELETE FROM employees WHERE employee_number='NPM999995'");$exists=(int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='npmgateway_test' AND TABLE_NAME='employees' AND COLUMN_NAME='date_of_birth'")->fetch_row()[0];if($exists===0)$migration->up($db);$db->close();}
    }
}
