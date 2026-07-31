<?php

declare(strict_types=1);

use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use PHPUnit\Framework\TestCase;

final class AuthenticationSecurityMigrationIntegrationTest extends TestCase
{
    public function testGuardedRollbackAndReapplyCycle(): void
    {
        if (getenv('RUN_DB_INTEGRATION_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DB_INTEGRATION_TESTS=true to run destructive local migration tests.');
        }

        $application = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $migration = DatabaseProfiles::load('migration', $application['root']);
        $applicationDatabase = DatabaseProfiles::load('application', $application['root']);
        foreach ([$migration, $applicationDatabase] as $config) {
            self::assertContains($config['app_env'], ['local', 'testing'], 'Destructive tests require local/testing.');
            self::assertContains($config['host'], ['127.0.0.1', 'localhost', '::1'], 'Destructive tests require exact loopback.');
        }
        $directory = $application['root'] . '/database/migrations';
        $initialStatus = implode("\n", MigrationCommand::execute('migrate:status', $migration, $directory));
        if (preg_match('/202607270002_authentication_security\\s+Ran/', $initialStatus) === 1) {
            $rollback = self::rollbackAll($migration,$directory);
            self::assertStringContainsString('202607270002_authentication_security', $rollback);
            $initialStatus = implode("\n", MigrationCommand::execute('migrate:status', $migration, $directory));
        }
        self::assertMatchesRegularExpression('/202607270001_foundation\\s+Pending/', $initialStatus);
        self::assertMatchesRegularExpression('/202607270002_authentication_security\\s+Pending/', $initialStatus);

        MigrationCommand::execute('migrate', $migration, $directory);
        self::assertSecurityState($migration, true);
        self::assertStringContainsString('Schema verification passed.', implode("\n", MigrationCommand::execute('schema:verify', $migration, $directory)));

        $finalStatus = implode("\n", MigrationCommand::execute('migrate:status', $migration, $directory));
        self::assertMatchesRegularExpression('/202607270001_foundation\\s+Ran/', $finalStatus);
        self::assertMatchesRegularExpression('/202607270002_authentication_security\\s+Ran/', $finalStatus);
        self::assertStringContainsString('Pending migrations: 0', implode("\n", MigrationCommand::execute('schema:verify', $migration, $directory)));
    }

    private static function rollbackAll(array $migration,string $directory):string
    {
        $messages=[];for($attempt=0;$attempt<5;$attempt++){$status=implode("\n",MigrationCommand::execute('migrate:status',$migration,$directory));if(!str_contains($status,'Ran'))break;$messages[]=implode("\n",MigrationCommand::execute('migrate:rollback',$migration,$directory));}return implode("\n",$messages);
    }

    /** @param array<string, mixed> $config */
    private static function assertSecurityState(array $config, bool $migrated): void
    {
        $connection = MySqlConnectionFactory::connect($config);
        try {
            foreach (['user_sessions', 'login_attempts'] as $table) {
                $result = $connection->query("SHOW TABLES LIKE '{$table}'");
                self::assertSame($migrated ? 1 : 0, $result->num_rows, $table);
                $result->free();
                if ($migrated) {
                    $result = $connection->query("SELECT COUNT(*) FROM `{$table}`");
                    self::assertSame(0, (int) $result->fetch_row()[0], "{$table} must be empty.");
                    $result->free();
                }
            }
            $result = $connection->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
            self::assertSame($migrated ? 0 : 1, $result->num_rows);
            $result->free();
            $result = $connection->query("SHOW TABLES LIKE 'migrations'");
            self::assertSame(1, $result->num_rows);
            $result->free();
        } finally {
            $connection->close();
        }
    }

    /** @param array<string, mixed> $config */
    private static function assertRestoredColumn(array $config): void
    {
        $connection = MySqlConnectionFactory::connect($config);
        try {
            $result = $connection->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
            $column = $result->fetch_assoc();
            $result->free();
            self::assertIsArray($column);
            self::assertSame('0', (string) $column['Default']);
        } finally {
            $connection->close();
        }
    }
}
