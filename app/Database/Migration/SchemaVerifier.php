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
        ) {
            throw new MigrationException('The database character set is invalid.');
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
        $executed = array_fill_keys(
            array_map(static fn (MigrationRecord $record): string => $record->migration, $records),
            true
        );
        if (isset($executed[FoundationSchema::MIGRATION])) {
            $this->verifyFoundationSchema();
        }

        return [
            'Schema verification passed.',
            sprintf('Executed migrations: %d', count($records)),
            sprintf('Pending migrations: %d', $pending),
        ];
    }

    private function verifyFoundationSchema(): void
    {
        foreach (FoundationSchema::expectations() as $table => $expected) {
            $metadata = $this->tableMetadata($table);
            if (strcasecmp($metadata['engine'], 'InnoDB') !== 0) {
                throw new MigrationException("{$table} must use InnoDB.");
            }
            if (strcasecmp($metadata['collation'], 'utf8mb4_0900_ai_ci') !== 0) {
                throw new MigrationException("{$table} has an invalid collation.");
            }
            foreach ($expected['indexes'] as $index) {
                if (!isset($metadata['indexes'][$index])) {
                    throw new MigrationException("Missing index {$index} on {$table}.");
                }
            }
            foreach ($expected['foreign_keys'] as $foreignKey) {
                if (!isset($metadata['foreign_keys'][$foreignKey])) {
                    throw new MigrationException("Missing foreign key {$foreignKey} on {$table}.");
                }
                if ($metadata['foreign_keys'][$foreignKey] === 'CASCADE') {
                    throw new MigrationException("Unexpected ON DELETE CASCADE on {$foreignKey}.");
                }
            }
            foreach ($expected['checks'] as $check) {
                if (!isset($metadata['checks'][$check])) {
                    throw new MigrationException("Missing check constraint {$check} on {$table}.");
                }
            }
        }

        $auditColumns = $this->tableMetadata('audit_logs')['columns'];
        foreach (['updated_at', 'created_by', 'updated_by'] as $forbidden) {
            if (isset($auditColumns[$forbidden])) {
                throw new MigrationException("audit_logs must not contain {$forbidden}.");
            }
        }
    }

    /**
     * @return array{
     *   engine: string,
     *   collation: string,
     *   columns: array<string, true>,
     *   indexes: array<string, true>,
     *   foreign_keys: array<string, string>,
     *   checks: array<string, true>
     * }
     */
    private function tableMetadata(string $table): array
    {
        $statement = $this->connection->prepare(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $database = $this->expectedDatabase;
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($row)) {
            throw new MigrationException("Missing Foundation table: {$table}.");
        }

        $columns = $this->namedSet(
            'SELECT COLUMN_NAME AS item_name FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            $table
        );
        $indexes = $this->namedSet(
            'SELECT DISTINCT INDEX_NAME AS item_name FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            $table
        );
        $checks = $this->namedSet(
            "SELECT CONSTRAINT_NAME AS item_name FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'CHECK'",
            $table
        );

        $statement = $this->connection->prepare(
            'SELECT CONSTRAINT_NAME, DELETE_RULE
             FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $result = $statement->get_result();
        $foreignKeys = [];
        while ($foreignKey = $result->fetch_assoc()) {
            $foreignKeys[(string) $foreignKey['CONSTRAINT_NAME']] = strtoupper((string) $foreignKey['DELETE_RULE']);
        }
        $statement->close();

        return [
            'engine' => (string) $row['ENGINE'],
            'collation' => (string) $row['TABLE_COLLATION'],
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
            'checks' => $checks,
        ];
    }

    /** @return array<string, true> */
    private function namedSet(string $sql, string $table): array
    {
        $statement = $this->connection->prepare($sql);
        $database = $this->expectedDatabase;
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $result = $statement->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[(string) $row['item_name']] = true;
        }
        $statement->close();

        return $items;
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
