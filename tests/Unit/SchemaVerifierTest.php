<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\SchemaVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchemaVerifierTest extends TestCase
{
    public function testValidMigrationsTableAccepted(): void
    {
        SchemaVerifier::validateTableDefinition(self::validDefinition());
        self::addToAssertionCount(1);
    }

    #[DataProvider('invalidDefinitions')]
    public function testInvalidDefinitionRejected(callable $mutate): void
    {
        $definition = self::validDefinition();
        $mutate($definition);
        $this->expectException(MigrationException::class);

        SchemaVerifier::validateTableDefinition($definition);
    }

    /**
     * @return iterable<string, array{callable(array): void}>
     */
    public static function invalidDefinitions(): iterable
    {
        yield 'wrong engine' => [static function (array &$definition): void {
            $definition['engine'] = 'MyISAM';
        }];
        yield 'wrong collation' => [static function (array &$definition): void {
            $definition['collation'] = 'utf8mb4_general_ci';
        }];
        yield 'missing column' => [static function (array &$definition): void {
            array_pop($definition['columns']);
        }];
        yield 'wrong type' => [static function (array &$definition): void {
            $definition['columns'][0]['COLUMN_TYPE'] = 'int unsigned';
        }];
        yield 'missing unique index' => [static function (array &$definition): void {
            $definition['indexes'] = array_values(array_filter(
                $definition['indexes'],
                static fn (array $index): bool => $index['INDEX_NAME'] !== 'uq_migrations_migration'
            ));
        }];
        yield 'missing batch index' => [static function (array &$definition): void {
            $definition['indexes'] = array_values(array_filter(
                $definition['indexes'],
                static fn (array $index): bool => $index['INDEX_NAME'] !== 'idx_migrations_batch'
            ));
        }];
    }

    /**
     * @return array{engine: string, collation: string, columns: list<array<string, string|null>>, indexes: list<array<string, string>>}
     */
    private static function validDefinition(): array
    {
        return [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_0900_ai_ci',
            'columns' => [
                ['COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'bigint unsigned', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => 'auto_increment'],
                ['COLUMN_NAME' => 'migration', 'COLUMN_TYPE' => 'varchar(255)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => ''],
                ['COLUMN_NAME' => 'batch', 'COLUMN_TYPE' => 'int unsigned', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => ''],
                ['COLUMN_NAME' => 'executed_at', 'COLUMN_TYPE' => 'datetime', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => 'CURRENT_TIMESTAMP', 'EXTRA' => 'DEFAULT_GENERATED'],
            ],
            'indexes' => [
                ['INDEX_NAME' => 'PRIMARY', 'NON_UNIQUE' => '0', 'SEQ_IN_INDEX' => '1', 'COLUMN_NAME' => 'id'],
                ['INDEX_NAME' => 'uq_migrations_migration', 'NON_UNIQUE' => '0', 'SEQ_IN_INDEX' => '1', 'COLUMN_NAME' => 'migration'],
                ['INDEX_NAME' => 'idx_migrations_batch', 'NON_UNIQUE' => '1', 'SEQ_IN_INDEX' => '1', 'COLUMN_NAME' => 'batch'],
            ],
        ];
    }
}
