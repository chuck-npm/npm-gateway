<?php

declare(strict_types=1);

use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\TestCase;

final class MySqlConnectionFactoryTest extends TestCase
{
    public function testValidationDoesNotRequireAConnection(): void
    {
        $config = MySqlConnectionFactory::validate([
            'host' => 'managed-mysql.example.invalid',
            'port' => '16751',
            'database' => 'gateway',
            'username' => 'application_user',
            'password' => 'secret',
            'ssl_ca' => '',
        ]);

        self::assertSame(16751, $config['port']);
        self::assertSame('managed-mysql.example.invalid', $config['host']);
    }

    public function testMissingRequiredEnvironmentValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"host" is required');

        MySqlConnectionFactory::validate([
            'host' => '',
            'port' => '16751',
            'database' => 'gateway',
            'username' => 'application_user',
            'password' => 'secret',
            'ssl_ca' => '',
        ]);
    }

    public function testDefaultMySqlPortIsNotAssumed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"port" is required');

        MySqlConnectionFactory::validate([
            'host' => 'managed-mysql.example.invalid',
            'database' => 'gateway',
            'username' => 'application_user',
            'password' => 'secret',
        ]);
    }

    public function testInvalidPortIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"port" must be an integer');

        MySqlConnectionFactory::validate([
            'host' => 'managed-mysql.example.invalid',
            'port' => '3306x',
            'database' => 'gateway',
            'username' => 'application_user',
            'password' => 'secret',
            'ssl_ca' => '',
        ]);
    }

    public function testConfiguredCaMustBeReadableWithoutOpeningAConnection(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TLS CA file is missing or unreadable');

        MySqlConnectionFactory::validate([
            'host' => 'managed-mysql.example.invalid',
            'port' => '16751',
            'database' => 'gateway',
            'username' => 'application_user',
            'password' => 'secret',
            'ssl_ca' => __DIR__ . '/not-present-ca.pem',
        ]);
    }
}
