<?php
declare(strict_types=1);

use NpmGateway\Console\BootstrapAdministratorCommand;
use NpmGateway\Console\ConsoleIoInterface;
use NpmGateway\Container\Container;
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Contracts\CredentialNotifierInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\EmployeeStoreInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Contracts\PasswordGeneratorInterface;
use NpmGateway\Contracts\SystemInitializationInterface;
use NpmGateway\Contracts\UserStoreInterface;
use NpmGateway\Exceptions\Container\CircularDependencyException;
use NpmGateway\Exceptions\Container\ServiceNotFoundException;
use NpmGateway\Exceptions\Domain\AdministratorAlreadyInitializedException;
use NpmGateway\Exceptions\Domain\UnsafeBootstrapEnvironmentException;
use NpmGateway\Exceptions\Domain\DuplicateEmployeeNumberException;
use NpmGateway\Exceptions\Domain\InvalidEmployeeDataException;
use NpmGateway\Exceptions\Domain\InvalidUsernameException;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\EmployeeService;
use NpmGateway\Services\NotificationService;
use NpmGateway\Services\PasswordService;
use NpmGateway\Services\UserService;
use NpmGateway\Services\SystemInitializationService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\Support\SecurePasswordGenerator;
use NpmGateway\ValueObjects\CredentialNotice;
use NpmGateway\ValueObjects\InitializeAdministratorRequest;
use NpmGateway\ValueObjects\InitializeAdministratorResult;
use PHPUnit\Framework\TestCase;

final class BootstrapAdministrationTest extends TestCase
{
    public function testContainerResolvesSharedServicesAndRejectsUnknownAndCircularDefinitions(): void
    {
        $container = new Container();
        $container->set('known', static fn (): object => new stdClass());
        self::assertSame($container->get('known'), $container->get('known'));
        try { $container->get('missing'); self::fail('Expected missing service exception.'); }
        catch (ServiceNotFoundException) { self::addToAssertionCount(1); }
        $container->set('a', static fn (Container $c): mixed => $c->get('b'));
        $container->set('b', static fn (Container $c): mixed => $c->get('a'));
        $this->expectException(CircularDependencyException::class);
        $container->get('a');
    }

    public function testSecurePasswordGenerationAndHashing(): void
    {
        $generator = new SecurePasswordGenerator(32);
        $first = $generator->generate();
        $second = $generator->generate();
        self::assertSame(32, strlen($first));
        self::assertNotSame($first, $second);
        self::assertDoesNotMatchRegularExpression('/[0O1lI]/', $first);
        $credential = (new PasswordService(new class implements PasswordGeneratorInterface {
            public function generate(): string { return 'TEST-only-secure-material-abcdef'; }
        }))->generate();
        self::assertNotSame('TEST-only-secure-material-abcdef', $credential->passwordHash);
        self::assertTrue(password_verify('TEST-only-secure-material-abcdef', $credential->passwordHash));
        self::assertStringNotContainsString('TEST-only', json_encode($credential, JSON_THROW_ON_ERROR));
        self::assertFalse(method_exists($credential, '__toString'));
    }

    public function testEmployeeServiceNormalizesAndCreatesCorporateEmployee(): void
    {
        $store = new MemoryEmployeeStore();
        $service = new EmployeeService($store, new PublicIdGenerator());
        $employee = $service->createBootstrapCorporate($this->request(), '2026-07-27');
        self::assertSame('NPM000001', $employee['employee_number']);
        self::assertSame('corporate', $store->inserted['employee_class']);
        self::assertSame('active', $store->inserted['employment_status']);
        self::assertSame('Admin@npm.example', $store->inserted['business_email']);
        self::assertArrayNotHasKey('property_id', $store->inserted);
    }

    public function testEmployeeValidationAndDuplicateProtection(): void
    {
        $store = new MemoryEmployeeStore();
        $store->duplicate = true;
        $service = new EmployeeService($store, new PublicIdGenerator());
        $this->expectException(DuplicateEmployeeNumberException::class);
        $service->createBootstrapCorporate($this->request(), '2026-07-27');
    }

    public function testEmployeeRejectsInvalidEmailAndRequiredNames(): void
    {
        $service = new EmployeeService(new MemoryEmployeeStore(), new PublicIdGenerator());
        foreach ([
            new InitializeAdministratorRequest('NPM000001', '', 'Admin', 'Director', 'admin@npm.example', null, null, null, 'admin'),
            new InitializeAdministratorRequest('NPM000001', 'Test', 'Admin', 'Director', "bad\r\n@example.com", null, null, null, 'admin'),
        ] as $request) {
            try { $service->createBootstrapCorporate($request, '2026-07-27'); self::fail('Invalid employee accepted.'); }
            catch (InvalidEmployeeDataException) { self::addToAssertionCount(1); }
        }
    }

    public function testUserServiceNormalizesAndStoresOnlyHash(): void
    {
        $store = new MemoryUserStore();
        $user = (new UserService($store, new PublicIdGenerator()))->createBootstrapUser(7, ' Admin2 ', '$test-hash', '2026-07-27 10:00:00');
        self::assertSame('admin2', $user['username']);
        self::assertSame(7, $store->inserted['employee_id']);
        self::assertSame('$test-hash', $store->inserted['password_hash']);
        self::assertSame('active', $store->inserted['status']);
        self::assertArrayNotHasKey('password', $store->inserted);
        self::assertArrayNotHasKey('session', $store->inserted);
    }

    public function testUserRejectsInvalidUsername(): void
    {
        $this->expectException(InvalidUsernameException::class);
        (new UserService(new MemoryUserStore(), new PublicIdGenerator()))->createBootstrapUser(1, '2admin', '$hash', '2026-07-27 10:00:00');
    }

    public function testAuditServiceAcceptsSafeEventsAndRejectsSensitiveMetadata(): void
    {
        $store = new MemoryAuditStore();
        $service = new AuditService($store, new PublicIdGenerator());
        $service->record('system.administrator_initialized', 1, 2, str_repeat('A', 26), 'Initialized.', ['username' => 'admin'], '2026-07-27 10:00:00');
        self::assertSame('system.administrator_initialized', $store->inserted[0]['event_type']);
        foreach (['password', 'password_hash', 'session_token_hash'] as $key) {
            try { $service->record('test', 1, 2, str_repeat('A', 26), 'Test.', [$key => 'prohibited'], '2026-07-27 10:00:00'); self::fail('Sensitive metadata accepted.'); }
            catch (InvalidArgumentException) { self::addToAssertionCount(1); }
        }
    }

    public function testNotificationPolicyAndSafeFailure(): void
    {
        $notifier = new MemoryNotifier();
        $service = new NotificationService($notifier, [
            'environment' => 'testing', 'configured' => true, 'allow_local_fallback' => true,
        ]);
        $notice = new CredentialNotice('admin@example.test', 'Admin', 'secure - credentials', 'Test Admin', 'NPM000001', 'admin', 'TEST-secret-material', 'Director', null, '2026-07-27 10:00:00');
        self::assertSame('sent', $service->send($notice, false));
        self::assertSame($notice, $notifier->notice);
        self::assertSame('skipped-local', $service->send($notice, true));
        self::assertStringNotContainsString('TEST-secret', json_encode($notice, JSON_THROW_ON_ERROR));
        $this->expectException(UnsafeBootstrapEnvironmentException::class);
        (new NotificationService($notifier, ['environment' => 'production', 'configured' => false, 'allow_local_fallback' => false]))->validateMode(false);
    }

    public function testCliRejectsPasswordOptionAndMapsAlreadyInitialized(): void
    {
        $io = new MemoryIo();
        $system = new MemoryInitializationService();
        $command = new BootstrapAdministratorCommand($system, $io);
        self::assertSame(BootstrapAdministratorCommand::UNSAFE_ENVIRONMENT, $command->run(['--password=bad']));
        $system->exception = new AdministratorAlreadyInitializedException('Gateway has already been initialized.');
        self::assertSame(BootstrapAdministratorCommand::ALREADY_INITIALIZED, $command->run($this->arguments()));
    }

    public function testInitializationOwnsTransactionLockCommitAndPostCommitNotification(): void
    {
        $transaction = new MemoryTransaction();
        $users = new MemoryUserStore();
        $employees = new MemoryEmployeeStore();
        $audits = new MemoryAuditStore();
        $notifier = new MemoryNotifier();
        $service = $this->initializationService($transaction, $users, $employees, $audits, $notifier);
        $result = $service->initialize($this->request());
        self::assertSame(['acquire', 'begin', 'commit', 'release'], $transaction->operations);
        self::assertSame('sent', $result->credentialNotificationStatus);
        self::assertCount(2, $audits->inserted);
        self::assertSame('system.administrator_initialized', $audits->inserted[0]['event_type']);
        self::assertSame('system.administrator_credential_notice_sent', $audits->inserted[1]['event_type']);
        self::assertNotNull($notifier->notice);
        self::assertArrayNotHasKey('password', $users->inserted);
    }

    public function testInitializationRollsBackAndReleasesLockOnPersistenceFailure(): void
    {
        $transaction = new MemoryTransaction();
        $users = new MemoryUserStore();
        $employees = new MemoryEmployeeStore();
        $audits = new MemoryAuditStore();
        $audits->fail = true;
        try {
            $this->initializationService($transaction, $users, $employees, $audits, new MemoryNotifier())->initialize($this->request());
            self::fail('Persistence failure did not escape.');
        } catch (RuntimeException) {
            self::assertSame(['acquire', 'begin', 'rollback', 'release'], $transaction->operations);
        }
    }

    public function testInitializationRefusesExistingUserBeforeLock(): void
    {
        $transaction = new MemoryTransaction();
        $users = new MemoryUserStore();
        $users->exists = true;
        $this->expectException(AdministratorAlreadyInitializedException::class);
        try {
            $this->initializationService($transaction, $users, new MemoryEmployeeStore(), new MemoryAuditStore(), new MemoryNotifier())->initialize($this->request());
        } finally {
            self::assertSame([], $transaction->operations);
        }
    }

    public function testCliDisplaysPasswordOnceOnlyAfterSuccessfulServiceResult(): void
    {
        $io = new MemoryIo();
        $system = new MemoryInitializationService();
        $system->result = new InitializeAdministratorResult(str_repeat('E', 26), str_repeat('U', 26), 'NPM000001', 'admin', 'TEST-one-time-password-value', 'skipped-local', null, '2026-07-27 10:00:00');
        $command = new BootstrapAdministratorCommand($system, $io);
        self::assertSame(0, $command->run($this->arguments()));
        self::assertSame(1, substr_count($io->stdout, 'TEST-one-time-password-value'));
        self::assertStringNotContainsString('TEST-one-time-password-value', substr($io->stdout, 0, (int) strpos($io->stdout, 'Administrator created successfully.')));
    }

    public function testStaticArchitectureBoundaries(): void
    {
        $root = dirname(__DIR__, 2);
        $command = (string) file_get_contents($root . '/app/Console/BootstrapAdministratorCommand.php');
        self::assertStringNotContainsString('mysqli', $command);
        self::assertDoesNotMatchRegularExpression('/\\b(?:INSERT|UPDATE|DELETE)\\b/i', $command);
        self::assertStringNotContainsString('Repositories\\', $command);
        foreach (glob($root . '/app/Repositories/*.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);
            self::assertStringNotContainsString('begin_transaction', $source);
            self::assertStringNotContainsString('Services\\', $source);
        }
        self::assertStringNotContainsString('--password=', (string) file_get_contents($root . '/README.md'));
        self::assertStringNotContainsString('password VARCHAR', (string) file_get_contents($root . '/database/migrations/202607270002_authentication_security.php'));
    }

    private function request(): InitializeAdministratorRequest
    {
        return new InitializeAdministratorRequest(' npm000001 ', ' Test ', ' Admin ', ' Director ', 'Admin@NPM.EXAMPLE', null, null, null, 'admin');
    }
    private function initializationService(
        MemoryTransaction $transaction,
        MemoryUserStore $users,
        MemoryEmployeeStore $employees,
        MemoryAuditStore $audits,
        MemoryNotifier $notifier
    ): SystemInitializationService {
        $ids = new PublicIdGenerator();
        $config = [
            'environment' => 'testing', 'configured' => true, 'allow_local_fallback' => true,
            'recipient_email' => 'admin@example.test', 'recipient_name' => 'Admin',
            'subject' => 'secure - test credentials',
        ];
        return new SystemInitializationService(
            $transaction, $users, new EmployeeService($employees, $ids), new UserService($users, $ids),
            new PasswordService(new class implements PasswordGeneratorInterface {
                public function generate(): string { return 'TEST-only-secure-material-abcdef'; }
            }),
            new AuditService($audits, $ids), new NotificationService($notifier, $config),
            new class implements ClockInterface {
                public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-07-27 10:00:00'); }
            },
            $config
        );
    }
    /** @return list<string> */
    private function arguments(): array
    {
        return ['--employee-number=NPM000001', '--first-name=Test', '--last-name=Admin', '--job-title=Director', '--business-email=admin@example.test', '--username=admin', '--no-notification', '--acknowledge-no-notification', '--yes'];
    }
}

final class MemoryEmployeeStore implements EmployeeStoreInterface {
    public bool $duplicate = false; public array $inserted = [];
    public function employeeNumberExists(string $employeeNumber): bool { return $this->duplicate; }
    public function insert(array $employee): int { $this->inserted = $employee; return 11; }
}
final class MemoryUserStore implements UserStoreInterface {
    public bool $exists = false; public bool $duplicate = false; public array $inserted = [];
    public function anyExists(): bool { return $this->exists; }
    public function usernameExists(string $username): bool { return $this->duplicate; }
    public function insert(array $user): int { $this->inserted = $user; return 12; }
}
final class MemoryAuditStore implements AuditStoreInterface {
    public array $inserted = []; public bool $fail = false;
    public function insert(array $event): void { if ($this->fail) { throw new RuntimeException('TEST persistence failure'); } $this->inserted[] = $event; }
}
final class MemoryTransaction implements InitializationTransactionInterface {
    public array $operations = []; public bool $lockAvailable = true;
    public function acquire(string $lockName, int $timeoutSeconds): bool { $this->operations[] = 'acquire'; return $this->lockAvailable; }
    public function begin(): void { $this->operations[] = 'begin'; }
    public function commit(): void { $this->operations[] = 'commit'; }
    public function rollback(): void { $this->operations[] = 'rollback'; }
    public function release(string $lockName): void { $this->operations[] = 'release'; }
}
final class MemoryNotifier implements CredentialNotifierInterface {
    public ?CredentialNotice $notice = null; public function notify(CredentialNotice $notice): void { $this->notice = $notice; }
}
final class MemoryInitializationService implements SystemInitializationInterface {
    public ?Throwable $exception = null; public ?InitializeAdministratorResult $result = null;
    public function initialize(InitializeAdministratorRequest $request): InitializeAdministratorResult {
        if ($this->exception) { throw $this->exception; }
        return $this->result ?? new InitializeAdministratorResult(str_repeat('E', 26), str_repeat('U', 26), 'NPM000001', 'admin', 'TEST-password-value-abcdef', 'skipped-local', null, '2026-07-27 10:00:00');
    }
}
final class MemoryIo implements ConsoleIoInterface {
    public string $stdout = ''; public string $stderr = ''; public array $inputs = [];
    public function read(string $prompt): string { $this->stdout .= $prompt; return array_shift($this->inputs) ?? ''; }
    public function write(string $text): void { $this->stdout .= $text; }
    public function error(string $text): void { $this->stderr .= $text; }
}
