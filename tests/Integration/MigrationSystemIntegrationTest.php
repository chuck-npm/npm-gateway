<?php

declare(strict_types=1);

use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\DatabaseProfiles;
use PHPUnit\Framework\TestCase;

final class MigrationSystemIntegrationTest extends TestCase
{
    public function testBootstrapStatusAndSchemaVerificationWithEmptyDirectory(): void
    {
        if (getenv('RUN_DB_INTEGRATION_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true to run live database tests.');
        }

        $application = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $config = DatabaseProfiles::load('migration', $application['root']);
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'npm_gateway_integration_' . bin2hex(random_bytes(8));
        mkdir($directory);

        try {
            self::assertSame(
                ['No pending migrations.'],
                MigrationCommand::execute('migrate', $config, $directory)
            );
            self::assertSame(
                ['No migration files found.'],
                MigrationCommand::execute('migrate:status', $config, $directory)
            );
            self::assertStringContainsString(
                'Schema verification passed.',
                implode("\n", MigrationCommand::execute('schema:verify', $config, $directory))
            );
        } finally {
            rmdir($directory);
        }
    }
}
