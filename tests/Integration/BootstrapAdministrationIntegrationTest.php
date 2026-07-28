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
use NpmGateway\Services\AuthenticationService;
use NpmGateway\Services\SessionService;
use NpmGateway\Services\LoginThrottleService;
use NpmGateway\Repositories\SessionRepository;
use NpmGateway\Repositories\LoginAttemptRepository;
use NpmGateway\Repositories\DashboardSummaryRepository;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Services\UniversalToolProvider;
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Security\AuthenticationHasher;
use NpmGateway\Support\SecureSessionTokenGenerator;
use NpmGateway\ValueObjects\LoginRequest;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\Exceptions\Domain\InvalidCredentialsException;
use NpmGateway\Exceptions\Domain\InvalidSessionException;
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

                $authConfig = new AuthenticationConfig('npm_gateway_session', false, true, 'Lax', 60, 8, 15, 5, 5, 15, 10, 10, str_repeat('T', 32));
                $hasher = new AuthenticationHasher($authConfig);
                $sessionRepository = new SessionRepository($connection);
                $attemptRepository = new LoginAttemptRepository($connection);
                $sessionService = new SessionService($sessionRepository, $users, new SecureSessionTokenGenerator(), $hasher, $ids, $authConfig, new AuditService($audits, $ids));
                $authentication = new AuthenticationService(
                    new MySqlInitializationTransaction($connection), $users, $attemptRepository,
                    new LoginThrottleService($attemptRepository, $authConfig), $sessionService,
                    new AuditService($audits, $ids), $hasher, $ids, $authConfig
                );
                $clock = new MutableAuthenticationClock(new DateTimeImmutable('2026-07-28 09:00:00'));
                try { $authentication->authenticate(new LoginRequest('unknown', 'TEST-invalid-password'), new ClientContext('192.0.2.10', 'Integration Agent', $clock->now())); self::fail('Unknown login succeeded.'); }
                catch (InvalidCredentialsException) { self::addToAssertionCount(1); }
                self::assertSame(0, (int) $connection->query('SELECT failed_login_count FROM users')->fetch_row()[0]);
                for ($failure = 1; $failure <= 5; $failure++) {
                    try { $authentication->authenticate(new LoginRequest('integrationadmin', 'TEST-wrong-password'), new ClientContext('192.0.2.10', 'Integration Agent', $clock->now())); self::fail('Invalid password succeeded.'); }
                    catch (InvalidCredentialsException) { self::addToAssertionCount(1); }
                }
                $locked = $connection->query('SELECT failed_login_count, locked_until FROM users')->fetch_assoc();
                self::assertSame(5, (int) $locked['failed_login_count']);
                self::assertNotNull($locked['locked_until']);
                self::assertSame(1, (int) $connection->query("SELECT COUNT(*) FROM audit_logs WHERE event_type='authentication.account_locked'")->fetch_row()[0]);
                $clock->advance('+16 minutes');
                $login = $authentication->authenticate(new LoginRequest('IntegrationAdmin', $result->generatedPassword()), new ClientContext('192.0.2.10', 'Integration Agent', $clock->now()));
                $dashboard = new DashboardSummaryService(new DashboardSummaryRepository($connection));
                $initialSummary = $dashboard->forUser($login->user);
                self::assertSame(0, $initialSummary->propertyCount);
                self::assertSame(1, $initialSummary->employeeCount);
                self::assertSame(1, $initialSummary->userCount);
                self::assertSame(1, $initialSummary->activeUserCount);
                self::assertSame(0, $initialSummary->activeAssignmentCount);
                self::assertTrue($initialSummary->initialSetup);
                $beforeDashboard=[
                    'properties'=>self::rowCount($connection,'properties'),
                    'employees'=>self::rowCount($connection,'employees'),
                    'users'=>self::rowCount($connection,'users'),
                    'assignments'=>self::rowCount($connection,'employee_property_assignments'),
                ];
                $home=(new DashboardHomeService($dashboard,new UniversalToolProvider()))->forUser($login->user);
                self::assertSame('Integration Administrator',$home->welcomeName);
                self::assertSame('Corporate',$home->employeeClassLabel);
                self::assertSame('Corporate Administrator',$home->jobTitle);
                self::assertCount(12,$home->universalTools);
                foreach($home->universalTools as $tool){self::assertFalse($tool->enabled);self::assertNull($tool->route);}
                self::assertSame($beforeDashboard,[
                    'properties'=>self::rowCount($connection,'properties'),
                    'employees'=>self::rowCount($connection,'employees'),
                    'users'=>self::rowCount($connection,'users'),
                    'assignments'=>self::rowCount($connection,'employee_property_assignments'),
                ]);
                $propertyId = $ids->generate();
                $property = $connection->prepare("INSERT INTO properties (public_id,property_code,slug,display_name,status,manager_email,ivr_number,address_line_1,city,state,postal_code,timezone) VALUES (?,'IT','integration-test','Integration Test Property','active','manager@integration.example.test','+1555010199','1 Test Way','Testville','OH','43000','America/New_York')");
                $property->bind_param('s',$propertyId);$property->execute();$property->close();
                $configuredSummary = $dashboard->forUser($login->user);
                self::assertSame(1, $configuredSummary->propertyCount);
                self::assertFalse($configuredSummary->initialSetup);
                self::assertSame(0, (int) $connection->query('SELECT failed_login_count FROM users')->fetch_row()[0]);
                self::assertSame(1, self::rowCount($connection, 'user_sessions'));
                $sessionRow = $connection->query('SELECT session_token_hash FROM user_sessions')->fetch_assoc();
                self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $sessionRow['session_token_hash']);
                self::assertNotSame($login->session->reveal(), $sessionRow['session_token_hash']);
                self::assertSame('integrationadmin', $sessionService->validate($login->session->reveal(), new ClientContext('192.0.2.10', 'Integration Agent', $clock->now()))->user->username);
                $clock->advance('+16 minutes');
                $rotated = $sessionService->validate($login->session->reveal(), new ClientContext('192.0.2.10', 'Integration Agent', $clock->now()));
                self::assertNotNull($rotated->rotatedToken);
                try { $sessionService->validate($login->session->reveal(), new ClientContext('192.0.2.10', null, $clock->now())); self::fail('Old token remained valid.'); }
                catch (InvalidSessionException) { self::addToAssertionCount(1); }
                $clock->advance('+61 minutes');
                try { $sessionService->validate($rotated->rotatedToken->reveal(), new ClientContext('192.0.2.10', null, $clock->now())); self::fail('Idle session remained valid.'); }
                catch (InvalidSessionException) { self::addToAssertionCount(1); }
                self::assertSame('idle_expired', $connection->query('SELECT revocation_reason FROM user_sessions')->fetch_row()[0]);
                $newSession = $sessionService->create($login->user, new ClientContext('192.0.2.10', null, $clock->now()));
                $clock->advance('+9 hours');
                try { $sessionService->validate($newSession->reveal(), new ClientContext('192.0.2.10', null, $clock->now())); self::fail('Absolute-expired session remained valid.'); }
                catch (InvalidSessionException) { self::addToAssertionCount(1); }
                $logoutSession = $sessionService->create($login->user, new ClientContext('192.0.2.10', null, $clock->now()));
                $sessionService->logout($logoutSession->reveal(), new ClientContext('192.0.2.10', null, $clock->now()));
                self::assertSame(1, (int) $connection->query("SELECT COUNT(*) FROM user_sessions WHERE revocation_reason='logout'")->fetch_row()[0]);
                self::assertSame(1, (int) $connection->query("SELECT COUNT(*) FROM audit_logs WHERE event_type='authentication.logout'")->fetch_row()[0]);
                $allAudit = (string) $connection->query('SELECT GROUP_CONCAT(after_data) FROM audit_logs')->fetch_row()[0];
                self::assertStringNotContainsString($result->generatedPassword(), $allAudit);
                self::assertStringNotContainsString($login->session->reveal(), $allAudit);
            } finally {
            if ($connection instanceof mysqli) {
                $connection->query('DELETE FROM properties');
                $connection->query('DELETE FROM audit_logs');
                $connection->query('DELETE FROM login_attempts');
                $connection->query('DELETE FROM user_sessions');
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
final class MutableAuthenticationClock
{
    public function __construct(private DateTimeImmutable $time) {}
    public function now(): DateTimeImmutable { return $this->time; }
    public function advance(string $modify): void { $this->time = $this->time->modify($modify); }
}
