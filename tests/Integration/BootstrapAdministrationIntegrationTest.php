<?php
declare(strict_types=1);

use NpmGateway\Console\MigrationCommand;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\CredentialNotifierInterface;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Database\MySqlInitializationTransaction;
use NpmGateway\Repositories\AuditRepository;
use NpmGateway\Repositories\EmployeeRepository;
use NpmGateway\Repositories\UserRepository;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\EmployeeService;
use NpmGateway\Services\NotificationService;
use NpmGateway\Services\PasswordService;
use NpmGateway\Services\SystemInitializationService;
use NpmGateway\Services\UserService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\Support\SecurePasswordGenerator;
use NpmGateway\ValueObjects\CredentialNotice;
use NpmGateway\ValueObjects\InitializeAdministratorRequest;
use PHPUnit\Framework\TestCase;

final class BootstrapAdministrationIntegrationTest extends TestCase
{
    public function testBootstrapInDisposableDatabase(): void
    {
        if (getenv('RUN_DB_INTEGRATION_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true.');
        }
        $application = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $database = (string) (
            $_ENV['BOOTSTRAP_INTEGRATION_DB_NAME']
            ?? $_SERVER['BOOTSTRAP_INTEGRATION_DB_NAME']
            ?? getenv('BOOTSTRAP_INTEGRATION_DB_NAME')
            ?: ''
        );
        if ($database === '') {
            self::markTestSkipped('Set BOOTSTRAP_INTEGRATION_DB_NAME to a disposable database ending in _test.');
        }
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9_]+_test$/', $database);
        $migration = DatabaseProfiles::load('migration', $application['root']);
        $applicationConfig = DatabaseProfiles::load('application', $application['root']);
        foreach ([$migration, $applicationConfig] as $config) {
            self::assertContains($config['app_env'], ['local', 'testing']);
            self::assertContains($config['host'], ['127.0.0.1', 'localhost', '::1']);
        }
        self::assertNotSame($migration['database'], $database);
        $testConfig = [...$migration, 'database' => $database];
        $connection = null;
        try {
            MigrationCommand::execute('migrate', $testConfig, $application['root'] . '/database/migrations');
            $connection = MySqlConnectionFactory::connect($testConfig);
            foreach (['properties', 'employees', 'users', 'employee_property_assignments', 'audit_logs', 'user_sessions', 'login_attempts'] as $table) {
                self::assertSame(0, self::rowCount($connection, $table), "Disposable {$table} must begin empty.");
            }
            $ids = new PublicIdGenerator();
            $employees = new EmployeeRepository($connection);
            $users = new UserRepository($connection);
            $audits = new AuditRepository($connection);
            $notifier = new IntegrationCredentialNotifier();
            $noticeConfig = [
                'environment' => 'testing', 'configured' => true, 'allow_local_fallback' => false,
                'recipient_email' => 'integration@example.test', 'recipient_name' => 'Integration',
                'subject' => 'secure - integration credentials',
            ];
            $service = new SystemInitializationService(
                new MySqlInitializationTransaction($connection), $users, new EmployeeService($employees, $ids), new UserService($users, $ids),
                new PasswordService(new SecurePasswordGenerator()), new AuditService($audits, $ids),
                new NotificationService($notifier, $noticeConfig),
                new class implements ClockInterface {
                    public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-07-27 12:00:00', new DateTimeZone('America/New_York')); }
                },
                $noticeConfig
            );
                $result = $service->initialize(new InitializeAdministratorRequest(
                    'NPM999999', 'Integration', 'Administrator', 'Corporate Administrator',
                    'integration@example.test', null, null, null, 'IntegrationAdmin'
                ));
                self::assertSame('integrationadmin', $result->username);
                self::assertSame('sent', $result->credentialNotificationStatus);
                self::assertSame(1, self::rowCount($connection, 'employees'));
                self::assertSame(1, self::rowCount($connection, 'users'));
                self::assertSame(2, self::rowCount($connection, 'audit_logs'));
                foreach (['properties', 'employee_property_assignments', 'user_sessions', 'login_attempts'] as $table) {
                    self::assertSame(0, self::rowCount($connection, $table));
                }
                $row = $connection->query('SELECT e.employee_class, e.employment_status, u.employee_id, u.password_hash
                    FROM users u JOIN employees e ON e.id = u.employee_id')->fetch_assoc();
                self::assertSame('corporate', $row['employee_class']);
                self::assertSame('active', $row['employment_status']);
                self::assertTrue(password_verify($result->generatedPassword(), $row['password_hash']));
                $audit = $connection->query('SELECT GROUP_CONCAT(after_data) FROM audit_logs')->fetch_row()[0];
                self::assertStringNotContainsString($result->generatedPassword(), (string) $audit);
                self::assertStringNotContainsString((string) $row['password_hash'], (string) $audit);
                try { $service->initialize(new InitializeAdministratorRequest('NPM999998', 'Other', 'Admin', 'Admin', 'other@example.test', null, null, null, 'other')); self::fail('Second bootstrap succeeded.'); }
                catch (NpmGateway\Exceptions\Domain\AdministratorAlreadyInitializedException) { self::addToAssertionCount(1); }
                self::assertSame(1, self::rowCount($connection, 'employees'));
                self::assertSame(1, self::rowCount($connection, 'users'));
                self::assertStringContainsString('Pending migrations: 0', implode("\n", MigrationCommand::execute('schema:verify', $testConfig, $application['root'] . '/database/migrations')));
        } finally {
            if ($connection instanceof mysqli) {
                $connection->query('DELETE FROM audit_logs');
                $connection->query('DELETE FROM users');
                $connection->query('DELETE FROM employees');
                foreach (['properties', 'employees', 'users', 'employee_property_assignments', 'audit_logs', 'user_sessions', 'login_attempts'] as $table) {
                    self::assertSame(0, self::rowCount($connection, $table), "Disposable {$table} cleanup failed.");
                }
                $connection->close();
            }
        }
    }
    private static function rowCount(mysqli $connection, string $table): int
    {
        return (int) $connection->query("SELECT COUNT(*) FROM `{$table}`")->fetch_row()[0];
    }
}
final class IntegrationCredentialNotifier implements CredentialNotifierInterface
{
    public function notify(CredentialNotice $notice): void {}
}
