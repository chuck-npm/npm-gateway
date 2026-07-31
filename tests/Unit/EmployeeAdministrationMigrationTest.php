<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\EmployeeAdministrationSchema;
use PHPUnit\Framework\TestCase;
final class EmployeeAdministrationMigrationTest extends TestCase
{
    public function testApprovedMigrationIsReversibleAndGuarded():void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/database/migrations/202607310005_employee_start_date_and_comments.php');self::assertSame('202607310005_employee_start_date_and_comments',EmployeeAdministrationSchema::MIGRATION);self::assertStringContainsString('CHANGE COLUMN hire_date start_date DATE NOT NULL',$source);self::assertStringContainsString('ADD COLUMN comments TEXT NULL',$source);self::assertStringContainsString('RENAME INDEX idx_employees_hire_date TO idx_employees_start_date',$source);self::assertStringContainsString('termination_date >= start_date',$source);self::assertStringContainsString('COUNT(*) FROM employees WHERE comments IS NOT NULL',$source);self::assertStringContainsString('CHANGE COLUMN start_date hire_date DATE NOT NULL',$source);self::assertStringContainsString('DROP COLUMN comments',$source);self::assertStringContainsString('termination_date >= hire_date',$source);
    }
    public function testVerifierRejectsDuplicateTerminologyAndRequiresDefinitions():void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Database/Migration/SchemaVerifier.php');foreach(['employees.hire_date must not exist','employees.start_date must be DATE NOT NULL','employees.comments must be nullable TEXT','idx_employees_start_date'] as $text)self::assertStringContainsString($text,$source);
    }
}
