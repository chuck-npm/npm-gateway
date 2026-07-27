<?php

declare(strict_types=1);

use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\TestCase;

final class FoundationMigrationIntegrationTest extends TestCase
{
    public function testFoundationRollbackAndReapplyCycle(): void
    {
        if (getenv('RUN_DB_INTEGRATION_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true to run destructive local migration tests.');
        }

        $application = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $config = DatabaseProfiles::load('migration', $application['root']);
        self::assertContains($config['app_env'], ['local', 'testing'], 'Destructive tests require local/testing.');
        self::assertContains($config['host'], ['127.0.0.1', 'localhost', '::1'], 'Destructive tests require exact loopback.');
        $directory = $application['root'] . '/database/migrations';

        MigrationCommand::execute('migrate', $config, $directory);
        self::assertFoundationState($config, true);
        self::assertStringContainsString('Schema verification passed.', implode("\n", MigrationCommand::execute('schema:verify', $config, $directory)));
        self::assertStringContainsString('Ran', implode("\n", MigrationCommand::execute('migrate:status', $config, $directory)));

        self::assertStringContainsString('Rolled back:', implode("\n", MigrationCommand::execute('migrate:rollback', $config, $directory)));
        self::assertFoundationState($config, false);
        self::assertStringContainsString('Pending migrations: 1', implode("\n", MigrationCommand::execute('schema:verify', $config, $directory)));

        self::assertStringContainsString('Migrated:', implode("\n", MigrationCommand::execute('migrate', $config, $directory)));
        self::assertFoundationState($config, true);
        self::assertStringContainsString('Pending migrations: 0', implode("\n", MigrationCommand::execute('schema:verify', $config, $directory)));
    }

    /** @param array<string, mixed> $config */
    private static function assertFoundationState(array $config, bool $present): void
    {
        $connection = MySqlConnectionFactory::connect($config);
        try {
            foreach (['properties', 'employees', 'users', 'employee_property_assignments', 'audit_logs'] as $table) {
                $statement = $connection->prepare(
                    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
                );
                $database = (string) $config['database'];
                $statement->bind_param('ss', $database, $table);
                $statement->execute();
                $count = (int) $statement->get_result()->fetch_row()[0];
                $statement->close();
                self::assertSame($present ? 1 : 0, $count, $table);
                if ($present) {
                    $result = $connection->query("SELECT COUNT(*) FROM `{$table}`");
                    self::assertSame(0, (int) $result->fetch_row()[0], "{$table} must remain empty.");
                    $result->free();
                }
            }
            $result = $connection->query("SHOW TABLES LIKE 'migrations'");
            self::assertSame(1, $result->num_rows);
            $result->free();
        } finally {
            $connection->close();
        }
    }
}
