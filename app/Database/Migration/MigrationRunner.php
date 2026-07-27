<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

use mysqli;
use Throwable;

final class MigrationRunner
{
    public function __construct(
        private readonly mysqli $connection,
        private readonly MigrationRepository $repository,
        private readonly MigrationDiscovery $discovery
    ) {
    }

    /**
     * @return list<string>
     */
    public function migrate(): array
    {
        $this->repository->ensureTable();
        $files = $this->discovery->discover();
        $executed = array_fill_keys(
            array_map(static fn (MigrationRecord $record): string => $record->migration, $this->repository->all()),
            true
        );
        $pending = self::pendingFiles($files, array_keys($executed));
        if ($pending === []) {
            return [];
        }

        $batch = $this->repository->nextBatch();
        return self::executePending(
            $pending,
            $batch,
            function (string $name): void {
                $this->discovery->load($name)->up($this->connection);
            },
            function (string $name, int $migrationBatch): void {
                $this->repository->record($name, $migrationBatch);
            }
        );
    }

    /**
     * @return list<string>
     */
    public function rollback(): array
    {
        $this->repository->ensureTable();
        $records = $this->repository->latestBatchRecords();
        if ($records === []) {
            return [];
        }

        return self::executeRollback(
            $records,
            function (string $name): void {
                $this->discovery->load($name)->down($this->connection);
            },
            function (string $name): void {
                $this->repository->delete($name);
            }
        );
    }

    /**
     * @return list<MigrationStatus>
     */
    public function status(): array
    {
        $this->repository->ensureTable();
        return self::buildStatus($this->discovery->discover(), $this->repository->all());
    }

    /**
     * @param list<array{name: string, filename: string, path: string}> $files
     * @param list<MigrationRecord> $records
     * @return list<MigrationStatus>
     */
    public static function buildStatus(array $files, array $records): array
    {
        $byName = [];
        foreach ($records as $record) {
            $byName[$record->migration] = $record;
        }

        $statuses = [];
        foreach ($files as $file) {
            $record = $byName[$file['name']] ?? null;
            $statuses[] = new MigrationStatus(
                $file['name'],
                $record ? 'Ran' : 'Pending',
                $record?->batch,
                $record?->executedAt
            );
            unset($byName[$file['name']]);
        }
        foreach ($byName as $record) {
            $statuses[] = new MigrationStatus(
                $record->migration,
                'Missing file',
                $record->batch,
                $record->executedAt
            );
        }

        return $statuses;
    }

    /**
     * @param list<array{name: string, filename: string, path: string}> $files
     * @param list<string> $executedNames
     * @return list<array{name: string, filename: string, path: string}>
     */
    public static function pendingFiles(array $files, array $executedNames): array
    {
        $executed = array_fill_keys($executedNames, true);
        return array_values(array_filter(
            $files,
            static fn (array $file): bool => !isset($executed[$file['name']])
        ));
    }

    /**
     * @param list<array{name: string, filename: string, path: string}> $pending
     * @param callable(string): mixed $runUp
     * @param callable(string, int): mixed $record
     * @return list<string>
     */
    public static function executePending(
        array $pending,
        int $batch,
        callable $runUp,
        callable $record
    ): array {
        $ran = [];
        foreach ($pending as $file) {
            $started = microtime(true);
            try {
                $runUp($file['name']);
                $record($file['name'], $batch);
            } catch (Throwable $exception) {
                throw new MigrationException(
                    "Migration failed: {$file['name']}. {$exception->getMessage()}",
                    0,
                    $exception
                );
            }
            $ran[] = sprintf('%s (%.3fs)', $file['name'], microtime(true) - $started);
        }

        return $ran;
    }

    /**
     * @param list<MigrationRecord> $records
     * @param callable(string): mixed $runDown
     * @param callable(string): mixed $delete
     * @return list<string>
     */
    public static function executeRollback(
        array $records,
        callable $runDown,
        callable $delete
    ): array {
        $rolledBack = [];
        foreach ($records as $record) {
            try {
                $runDown($record->migration);
                $delete($record->migration);
            } catch (Throwable $exception) {
                throw new MigrationException(
                    "Rollback failed: {$record->migration}. {$exception->getMessage()}",
                    0,
                    $exception
                );
            }
            $rolledBack[] = $record->migration;
        }

        return $rolledBack;
    }
}
