<?php

declare(strict_types=1);

use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\DatabaseProfiles;
use PHPUnit\Framework\TestCase;

final class MigrationSystemIntegrationTest extends TestCase
{
    public function testCurrentMigrationStatusAndSchemaVerification(): void
    {
        if (getenv('RUN_DB_INTEGRATION_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true to run live database tests.');
        }

        $application = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $config = DatabaseProfiles::load('migration', $application['root']);
        $directory = $application['root'] . '/database/migrations';
        self::assertNotEmpty(MigrationCommand::execute('migrate:status', $config, $directory));
        self::assertStringContainsString(
            'Schema verification passed.',
            implode("\n", MigrationCommand::execute('schema:verify', $config, $directory))
        );
    }
}
