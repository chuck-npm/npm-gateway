<?php

declare(strict_types=1);

use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MySqlConnectionFactoryTest extends TestCase
{
    #[DataProvider('localEnvironments')]
    public function testLocalLoopbackWithoutCaIsPermitted(string $environment): void
    {
        $config = MySqlConnectionFactory::validate($this->config('localhost', $environment));

        self::assertFalse($config['tls_required']);
        self::assertFalse($config['verify_server_certificate']);
        self::assertSame('', $config['ssl_ca']);
    }

    public static function localEnvironments(): iterable
    {
        yield 'local' => ['local'];
        yield 'testing' => ['testing'];
    }

    public function testRemoteHostWithoutCaIsRejectedInLocalEnvironment(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TLS CA file is required');

        MySqlConnectionFactory::validate($this->config('managed-mysql.example.invalid', 'local'));
    }

    public function testProductionLoopbackWithoutCaIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TLS CA file is required');

        MySqlConnectionFactory::validate($this->config('127.0.0.1', 'production'));
    }

    public function testManagedConnectionWithValidCaRequiresVerifiedTls(): void
    {
        $config = MySqlConnectionFactory::validate(
            $this->config('managed-mysql.example.invalid', 'production', __FILE__)
        );

        self::assertTrue($config['tls_required']);
        self::assertTrue($config['verify_server_certificate']);
        self::assertSame(__FILE__, $config['ssl_ca']);
    }

    #[DataProvider('remoteEnvironments')]
    public function testEmptyCaNeverDisablesRemoteTlsRequirement(string $environment): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TLS CA file is required');

        MySqlConnectionFactory::validate($this->config('10.0.0.5', $environment));
    }

    public static function remoteEnvironments(): iterable
    {
        yield 'local' => ['local'];
        yield 'testing' => ['testing'];
        yield 'production' => ['production'];
    }

    public function testMissingRequiredEnvironmentValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"host" is required');

        MySqlConnectionFactory::validate($this->config('', 'local'));
    }

    public function testDefaultMySqlPortIsNotAssumed(): void
    {
        $config = $this->config('localhost', 'local');
        unset($config['port']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"port" is required');

        MySqlConnectionFactory::validate($config);
    }

    public function testInvalidPortIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"port" must be an integer');

        MySqlConnectionFactory::validate([...$this->config('localhost', 'local'), 'port' => '3306x']);
    }

    public function testConfiguredCaMustBeReadableWithoutOpeningAConnection(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TLS CA file is missing or unreadable');

        MySqlConnectionFactory::validate(
            $this->config('managed-mysql.example.invalid', 'production', __DIR__ . '/not-present-ca.pem')
        );
    }

    /**
     * @return array<string, string>
     */
    private function config(string $host, string $environment, string $ca = ''): array
    {
        return [
            'host' => $host,
            'port' => '3306',
            'database' => 'gateway',
            'username' => 'application_user',
            'password' => 'secret',
            'ssl_ca' => $ca,
            'app_env' => $environment,
        ];
    }
}
