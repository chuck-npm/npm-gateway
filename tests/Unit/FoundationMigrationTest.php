<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationInterface;
use PHPUnit\Framework\TestCase;

final class FoundationMigrationTest extends TestCase
{
    private string $sql;
    private string $path;

    protected function setUp(): void
    {
        $this->path = dirname(__DIR__, 2) . '/database/migrations/202607270001_foundation.php';
        $this->sql = (string) file_get_contents($this->path);
    }

    public function testFilenameAndMigrationContract(): void
    {
        self::assertTrue(MigrationDiscovery::isValidFilename(basename($this->path)));
        self::assertInstanceOf(MigrationInterface::class, require $this->path);
    }

    public function testCreatesExactlyFiveTablesInDependencyOrder(): void
    {
        preg_match_all('/CREATE TABLE ([a-z_]+) \(/', $this->sql, $matches);
        self::assertSame(
            ['properties', 'employees', 'users', 'employee_property_assignments', 'audit_logs'],
            $matches[1]
        );
        self::assertLessThan(
            strpos($this->sql, 'CREATE TABLE employee_property_assignments'),
            strpos($this->sql, 'ALTER TABLE employees')
        );
    }

    public function testRollbackBreaksCircularReferencesAndUsesReverseOrder(): void
    {
        $down = substr($this->sql, (int) strpos($this->sql, 'public function down'));
        $tokens = [
            'DROP TABLE IF EXISTS audit_logs',
            'DROP TABLE IF EXISTS employee_property_assignments',
            'ALTER TABLE properties DROP FOREIGN KEY fk_properties_created_by',
            'ALTER TABLE employees DROP FOREIGN KEY fk_employees_updated_by',
            'DROP TABLE IF EXISTS users',
            'DROP TABLE IF EXISTS employees',
            'DROP TABLE IF EXISTS properties',
        ];
        $position = -1;
        foreach ($tokens as $token) {
            $next = strpos($down, $token);
            self::assertNotFalse($next);
            self::assertGreaterThan($position, $next);
            $position = $next;
        }
    }

    public function testSchemaUsesRequiredPortableStandardsAndNoForbiddenFeatures(): void
    {
        self::assertSame(5, substr_count($this->sql, 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'));
        self::assertSame(5, substr_count($this->sql, 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'));
        self::assertStringNotContainsString('ON DELETE CASCADE', strtoupper($this->sql));
        self::assertStringNotContainsString(' ENUM(', strtoupper($this->sql));
        self::assertStringNotContainsString('CREATE TRIGGER', strtoupper($this->sql));
        self::assertStringNotContainsString('FOREIGN_KEY_CHECKS', strtoupper($this->sql));
        self::assertStringNotContainsString('INSERT INTO', strtoupper($this->sql));
        self::assertStringNotContainsString('ALTER TABLE MIGRATIONS', strtoupper($this->sql));
    }

    public function testPropertiesEmployeesAndUsersDefinitions(): void
    {
        foreach ([
            'property_code CHAR(2) NOT NULL',
            'uq_properties_property_code',
            'uq_properties_slug',
            'uq_properties_manager_email',
            'uq_properties_ivr_number',
            'chk_properties_status',
            'chk_properties_code_format',
            'business_email VARCHAR(254)',
            'personal_email VARCHAR(254)',
            'company_phone VARCHAR(30)',
            'personal_phone VARCHAR(30)',
            'uq_employees_employee_number',
            'chk_employees_class',
            'chk_employees_status',
            'fk_employees_supervisor',
            'chk_employees_termination_dates',
            'uq_users_employee_id',
            'uq_users_username',
            'chk_users_username_format',
            'chk_users_username_lowercase',
            'chk_users_status',
            'password_hash VARCHAR(255)',
            'must_change_password TINYINT(1) NOT NULL DEFAULT 1',
            'password_reset_at DATETIME NULL',
        ] as $required) {
            self::assertStringContainsString($required, $this->sql);
        }
        self::assertStringNotContainsString('password_reset_token', $this->sql);
    }

    public function testAssignmentsAndAuditDefinitions(): void
    {
        foreach ([
            "'property_manager'", "'assistant_manager'", "'floating_manager'", "'maintenance'",
            "'temporary_coverage'", "'regional_support'", 'chk_assignments_date_range',
            'fk_assignments_employee', 'fk_assignments_property', 'idx_assignments_employee_active',
            'idx_assignments_property_active', 'before_data JSON NULL', 'after_data JSON NULL',
            'fk_audit_logs_user', 'fk_audit_logs_employee', 'fk_audit_logs_property',
            'idx_audit_logs_entity', 'idx_audit_logs_entity_public', 'chk_audit_logs_ip_hash',
        ] as $required) {
            self::assertStringContainsString($required, $this->sql);
        }
        $audit = substr(
            $this->sql,
            (int) strpos($this->sql, 'CREATE TABLE audit_logs'),
            (int) strpos($this->sql, "COMMENT='Append-only") - (int) strpos($this->sql, 'CREATE TABLE audit_logs')
        );
        self::assertStringNotContainsString('updated_at', $audit);
        self::assertStringNotContainsString('created_by', $audit);
        self::assertStringNotContainsString('updated_by', $audit);
    }
}
