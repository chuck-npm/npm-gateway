<?php

declare(strict_types=1);

namespace NpmGateway\Console;

use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationRepository;
use NpmGateway\Database\Migration\MigrationRunner;
use NpmGateway\Database\Migration\MigrationStatus;
use NpmGateway\Database\Migration\SchemaVerifier;
use NpmGateway\Database\MySqlConnectionFactory;

final class MigrationCommand
{
    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    public static function execute(
        string $command,
        array $config,
        string $migrationDirectory
    ): array {
        $validated = MySqlConnectionFactory::validate($config);
        $connection = MySqlConnectionFactory::connect($config);
        try {
            $repository = new MigrationRepository($connection);
            $discovery = new MigrationDiscovery($migrationDirectory);
            $runner = new MigrationRunner($connection, $repository, $discovery);

            return match ($command) {
                'migrate' => self::migrate($runner),
                'migrate:status' => self::status($runner),
                'migrate:rollback' => self::rollback($runner),
                'schema:verify' => (new SchemaVerifier(
                    $connection,
                    $repository,
                    $discovery,
                    $validated['database']
                ))->verify(),
                default => throw new \InvalidArgumentException('Unknown migration command.'),
            };
        } finally {
            $connection->close();
        }
    }

    /**
     * @return list<string>
     */
    private static function migrate(MigrationRunner $runner): array
    {
        $ran = $runner->migrate();
        return $ran === [] ? ['No pending migrations.'] : array_map(
            static fn (string $migration): string => "Migrated: {$migration}",
            $ran
        );
    }

    /**
     * @return list<string>
     */
    private static function rollback(MigrationRunner $runner): array
    {
        $rolledBack = $runner->rollback();
        return $rolledBack === [] ? ['Nothing to roll back.'] : array_map(
            static fn (string $migration): string => "Rolled back: {$migration}",
            $rolledBack
        );
    }

    /**
     * @return list<string>
     */
    private static function status(MigrationRunner $runner): array
    {
        $statuses = $runner->status();
        if ($statuses === []) {
            return ['No migration files found.'];
        }

        $lines = [
            sprintf('%-60s %-12s %-7s %s', 'Migration', 'Status', 'Batch', 'Executed At'),
        ];
        foreach ($statuses as $status) {
            $lines[] = self::formatStatus($status);
        }

        return $lines;
    }

    public static function formatStatus(MigrationStatus $status): string
    {
        return sprintf(
            '%-60s %-12s %-7s %s',
            $status->migration,
            $status->status,
            $status->batch === null ? '-' : (string) $status->batch,
            $status->executedAt ?? '-'
        );
    }
}
