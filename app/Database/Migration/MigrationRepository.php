<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

use mysqli;

final class MigrationRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function ensureTable(): void
    {
        $this->connection->query(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_migrations_migration (migration),
                KEY idx_migrations_batch (batch)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
        );
    }

    /**
     * @return list<MigrationRecord>
     */
    public function all(): array
    {
        $result = $this->connection->query(
            'SELECT migration, batch, executed_at FROM migrations ORDER BY id ASC'
        );
        $records = [];
        $seen = [];
        while ($row = $result->fetch_assoc()) {
            $name = (string) $row['migration'];
            if (isset($seen[$name])) {
                throw new MigrationException("Duplicate migration history record: {$name}");
            }
            $seen[$name] = true;
            $records[] = new MigrationRecord($name, (int) $row['batch'], (string) $row['executed_at']);
        }
        $result->free();

        return $records;
    }

    public function latestBatch(): int
    {
        $result = $this->connection->query('SELECT COALESCE(MAX(batch), 0) FROM migrations');
        $row = $result->fetch_row();
        $result->free();

        return (int) ($row[0] ?? 0);
    }

    public function nextBatch(): int
    {
        return self::nextBatchNumber($this->latestBatch());
    }

    public static function nextBatchNumber(int $latestBatch): int
    {
        return $latestBatch + 1;
    }

    public function record(string $migration, int $batch): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)'
        );
        $statement->bind_param('si', $migration, $batch);
        $statement->execute();
        $statement->close();
    }

    public function delete(string $migration): void
    {
        $statement = $this->connection->prepare('DELETE FROM migrations WHERE migration = ?');
        $statement->bind_param('s', $migration);
        $statement->execute();
        $statement->close();
    }

    /**
     * @return list<MigrationRecord>
     */
    public function latestBatchRecords(): array
    {
        $batch = $this->latestBatch();
        if ($batch === 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT migration, batch, executed_at
             FROM migrations WHERE batch = ? ORDER BY id DESC'
        );
        $statement->bind_param('i', $batch);
        $statement->execute();
        $result = $statement->get_result();
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = new MigrationRecord(
                (string) $row['migration'],
                (int) $row['batch'],
                (string) $row['executed_at']
            );
        }
        $statement->close();

        return $records;
    }

    /**
     * @return array{engine: string, collation: string, columns: list<array<string, string>>, indexes: list<array<string, string>>}
     */
    public function tableDefinition(string $database): array
    {
        $statement = $this->connection->prepare(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $table = 'migrations';
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $tableRow = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($tableRow)) {
            throw new MigrationException('The migrations table does not exist.');
        }

        $statement = $this->connection->prepare(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION'
        );
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $columns = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $statement = $this->connection->prepare(
            'SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX'
        );
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $indexes = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return [
            'engine' => (string) $tableRow['ENGINE'],
            'collation' => (string) $tableRow['TABLE_COLLATION'],
            'columns' => $columns,
            'indexes' => $indexes,
        ];
    }
}
