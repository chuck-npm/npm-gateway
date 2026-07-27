<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

use mysqli;

final class SchemaVerifier
{
    public function __construct(
        private readonly mysqli $connection,
        private readonly MigrationRepository $repository,
        private readonly MigrationDiscovery $discovery,
        private readonly string $expectedDatabase
    ) {
    }

    /**
     * @return list<string>
     */
    public function verify(): array
    {
        $selectedDatabase = $this->scalar('SELECT DATABASE()');
        if ($selectedDatabase !== $this->expectedDatabase) {
            throw new MigrationException('The selected database does not match MIGRATION_DB_NAME.');
        }
        if (strcasecmp($this->connection->character_set_name(), 'utf8mb4') !== 0) {
            throw new MigrationException('The connection character set is not utf8mb4.');
        }

        $statement = $this->connection->prepare(
            'SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
             FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'
        );
        $database = $this->expectedDatabase;
        $statement->bind_param('s', $database);
        $statement->execute();
        $schema = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (
            !is_array($schema)
            || strcasecmp((string) $schema['DEFAULT_CHARACTER_SET_NAME'], 'utf8mb4') !== 0
            || strcasecmp((string) $schema['DEFAULT_COLLATION_NAME'], 'utf8mb4_0900_ai_ci') !== 0
        ) {
            throw new MigrationException('The database character set or collation is invalid.');
        }

        self::validateTableDefinition($this->repository->tableDefinition($this->expectedDatabase));
        $records = $this->repository->all();
        foreach ($records as $record) {
            if ($record->migration === '' || $record->batch < 1) {
                throw new MigrationException('Migration history contains an invalid record.');
            }
        }

        $statuses = MigrationRunner::buildStatus($this->discovery->discover(), $records);
        $pending = 0;
        $orphaned = [];
        foreach ($statuses as $status) {
            if ($status->status === 'Pending') {
                $pending++;
            } elseif ($status->status === 'Missing file') {
                $orphaned[] = $status->migration;
            }
        }
        if ($orphaned !== []) {
            throw new MigrationException(
                'Orphaned migration records: ' . implode(', ', $orphaned)
            );
        }

        return [
            'Schema verification passed.',
            sprintf('Executed migrations: %d', count($records)),
            sprintf('Pending migrations: %d', $pending),
        ];
    }

    /**
     * @param array{engine: string, collation: string, columns: list<array<string, string>>, indexes: list<array<string, string>>} $definition
     */
    public static function validateTableDefinition(array $definition): void
    {
        if (strcasecmp($definition['engine'], 'InnoDB') !== 0) {
            throw new MigrationException('The migrations table engine must be InnoDB.');
        }
        if (strcasecmp($definition['collation'], 'utf8mb4_0900_ai_ci') !== 0) {
            throw new MigrationException('The migrations table collation is invalid.');
        }

        $expected = [
            'id' => ['bigint unsigned', 'NO', null, 'auto_increment'],
            'migration' => ['varchar(255)', 'NO', null, ''],
            'batch' => ['int unsigned', 'NO', null, ''],
            'executed_at' => ['datetime', 'NO', 'CURRENT_TIMESTAMP', 'DEFAULT_GENERATED'],
        ];
        if (count($definition['columns']) !== count($expected)) {
            throw new MigrationException('The migrations table has an unexpected column set.');
        }
        foreach ($definition['columns'] as $column) {
            $name = strtolower($column['COLUMN_NAME'] ?? '');
            if (!isset($expected[$name])) {
                throw new MigrationException("Unexpected migrations column: {$name}");
            }
            [$type, $nullable, $default, $extra] = $expected[$name];
            $actualExtra = strtoupper($column['EXTRA'] ?? '');
            if (
                strtolower($column['COLUMN_TYPE'] ?? '') !== $type
                || strtoupper($column['IS_NULLABLE'] ?? '') !== $nullable
                || ($column['COLUMN_DEFAULT'] ?? null) !== $default
                || ($extra !== '' && !str_contains($actualExtra, strtoupper($extra)))
                || ($extra === '' && $actualExtra !== '')
            ) {
                throw new MigrationException("Invalid migrations column definition: {$name}");
            }
        }

        $indexes = [];
        foreach ($definition['indexes'] as $index) {
            $name = (string) ($index['INDEX_NAME'] ?? '');
            $indexes[$name][] = [
                'column' => strtolower((string) ($index['COLUMN_NAME'] ?? '')),
                'non_unique' => (int) ($index['NON_UNIQUE'] ?? 1),
            ];
        }
        if (($indexes['PRIMARY'][0] ?? null) !== ['column' => 'id', 'non_unique' => 0]) {
            throw new MigrationException('The migrations primary key is invalid.');
        }
        if (
            ($indexes['uq_migrations_migration'][0] ?? null)
            !== ['column' => 'migration', 'non_unique' => 0]
        ) {
            throw new MigrationException('The migrations unique index is missing or invalid.');
        }
        if (
            ($indexes['idx_migrations_batch'][0] ?? null)
            !== ['column' => 'batch', 'non_unique' => 1]
        ) {
            throw new MigrationException('The migrations batch index is missing or invalid.');
        }
    }

    private function scalar(string $sql): string
    {
        $result = $this->connection->query($sql);
        $row = $result->fetch_row();
        $result->free();

        return (string) ($row[0] ?? '');
    }
}
