<?php

declare(strict_types=1);

use NpmGateway\Database\DatabaseDiagnostic;
use NpmGateway\Database\DatabaseProfiles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DatabaseDiagnosticIntegrationTest extends TestCase
{
    #[DataProvider('profiles')]
    public function testConfiguredDatabaseProfile(string $profile): void
    {
        if (getenv('RUN_DB_INTEGRATION_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true to run live database tests.');
        }

        $application = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $config = DatabaseProfiles::load($profile, $application['root']);
        $report = DatabaseDiagnostic::inspect($profile, $config);

        self::assertSame('successful', $report['connection']);
        self::assertContains($report['tls_active'], ['yes', 'no (permitted local loopback)']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function profiles(): iterable
    {
        yield 'application' => ['application'];
        yield 'migration' => ['migration'];
    }
}
