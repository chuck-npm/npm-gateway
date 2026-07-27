<?php

declare(strict_types=1);

use NpmGateway\Console\DatabaseCheckCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DatabaseCheckCommandTest extends TestCase
{
    /**
     * @param list<string> $arguments
     */
    #[DataProvider('invalidArguments')]
    public function testProfileArgumentValidation(array $arguments): void
    {
        $called = false;
        $result = DatabaseCheckCommand::run(
            $arguments,
            static function () use (&$called): array {
                $called = true;

                return [];
            }
        );

        self::assertSame(2, $result['exit_code']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('<application|migration>', $result['stderr']);
        self::assertFalse($called);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function invalidArguments(): iterable
    {
        yield 'missing arguments' => [[]];
        yield 'missing profile' => [['database:check']];
        yield 'unknown profile' => [['database:check', 'production']];
        yield 'extra argument' => [['database:check', 'application', 'extra']];
        yield 'unknown command' => [['database:test', 'application']];
    }

    public function testSafeOutputFormattingAndSuccessfulExitCode(): void
    {
        $result = DatabaseCheckCommand::run(
            ['database:check', 'application'],
            static fn (): array => self::report()
        );

        self::assertSame(0, $result['exit_code']);
        self::assertSame('', $result['stderr']);
        self::assertStringContainsString("Profile: application\n", $result['stdout']);
        self::assertStringContainsString("TLS active: yes\n", $result['stdout']);
        self::assertStringContainsString("TLS cipher: TLS_AES_256_GCM_SHA384\n", $result['stdout']);
        self::assertStringNotContainsString('password', strtolower($result['stdout']));
    }

    public function testFailureIsNonzeroAndCredentialsNeverAppear(): void
    {
        $password = 'diagnostic-secret-value';
        $result = DatabaseCheckCommand::run(
            ['database:check', 'migration'],
            static function () use ($password): array {
                throw new RuntimeException("Connection rejected for {$password}\ntrace-like detail");
            },
            [$password]
        );

        self::assertSame(1, $result['exit_code']);
        self::assertSame("Profile: migration\nConnection: failed\n", $result['stdout']);
        self::assertStringContainsString('[redacted]', $result['stderr']);
        self::assertStringNotContainsString($password, $result['stdout'] . $result['stderr']);
        self::assertStringNotContainsString("\ntrace-like detail", $result['stderr']);
    }

    /**
     * @return array<string, string>
     */
    private static function report(): array
    {
        return [
            'profile' => 'application',
            'connection' => 'successful',
            'database' => 'gateway',
            'server_version' => '8.0.40',
            'connection_charset' => 'utf8mb4',
            'tls_active' => 'yes',
            'tls_cipher' => 'TLS_AES_256_GCM_SHA384',
            'database_charset' => 'utf8mb4',
            'database_collation' => 'utf8mb4_0900_ai_ci',
            'privileges' => 'CRUD present; schema changes absent',
        ];
    }
}
