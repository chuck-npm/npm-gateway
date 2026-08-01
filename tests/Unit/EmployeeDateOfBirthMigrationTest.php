<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\EmployeeDateOfBirthSchema;
use PHPUnit\Framework\TestCase;
final class EmployeeDateOfBirthMigrationTest extends TestCase
{
    public function testMigrationAddsOnlyApprovedNullableDateWithoutIndexOrBackfill():void{$root=dirname(__DIR__,2);$source=(string)file_get_contents($root.'/database/migrations/202608010006_employee_date_of_birth.php');self::assertSame('202608010006_employee_date_of_birth',EmployeeDateOfBirthSchema::MIGRATION);self::assertStringContainsString('ADD COLUMN date_of_birth DATE NULL AFTER preferred_name',$source);self::assertStringNotContainsString('UPDATE employees',$source);self::assertStringNotContainsString('INDEX',$source);foreach([' dob ','birth_date','birthday',' age '] as $forbidden)self::assertStringNotContainsString($forbidden,$source);}
    public function testRollbackIsGuardedAgainstRestrictedDataLoss():void{$source=(string)file_get_contents(dirname(__DIR__,2).'/database/migrations/202608010006_employee_date_of_birth.php');self::assertStringContainsString('COUNT(*) FROM employees WHERE date_of_birth IS NOT NULL',$source);self::assertStringContainsString('rollback would destroy restricted employee data',$source);self::assertStringContainsString('DROP COLUMN date_of_birth',$source);}
    public function testVerifierRequiresExactPrivateColumnDefinition():void{$source=(string)file_get_contents(dirname(__DIR__,2).'/app/Database/Migration/SchemaVerifier.php');foreach(['employees.date_of_birth must be nullable DATE','after preferred_name',"['dob','birth_date','birthday','age']",'must not have a general-purpose index'] as $expected)self::assertStringContainsString($expected,$source);}
}
