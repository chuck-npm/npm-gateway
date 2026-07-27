<?php

declare(strict_types=1);

use NpmGateway\Console\MigrationCommand;
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationRecord;
use NpmGateway\Database\Migration\MigrationRepository;
use NpmGateway\Database\Migration\MigrationRunner;
use NpmGateway\Database\Migration\MigrationStatus;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testPendingDetectionCoversAllSomeNoneAndOrphans(): void
    {
        $files = self::files('one', 'two');
        self::assertSame(['one', 'two'], array_column(MigrationRunner::pendingFiles($files, []), 'name'));
        self::assertSame(['two'], array_column(MigrationRunner::pendingFiles($files, ['one']), 'name'));
        self::assertSame([], MigrationRunner::pendingFiles($files, ['one', 'two']));

        $status = MigrationRunner::buildStatus($files, [
            new MigrationRecord('one', 1, '2026-07-26 21:30:00'),
            new MigrationRecord('missing', 2, '2026-07-26 21:31:00'),
        ]);
        self::assertSame(['Ran', 'Pending', 'Missing file'], array_column($status, 'status'));
    }

    public function testBatchNumbersStartAtOneAndIncrement(): void
    {
        self::assertSame(1, MigrationRepository::nextBatchNumber(0));
        self::assertSame(8, MigrationRepository::nextBatchNumber(7));
    }

    public function testOneRunSharesOneBatch(): void
    {
        $recorded = [];
        MigrationRunner::executePending(
            self::files('one', 'two'),
            4,
            static fn (string $name) => null,
            static function (string $name, int $batch) use (&$recorded): void {
                $recorded[$name] = $batch;
            }
        );

        self::assertSame(['one' => 4, 'two' => 4], $recorded);
    }

    public function testFailedUpIsNotRecordedAndStopsLaterMigrations(): void
    {
        $ran = [];
        $recorded = [];
        try {
            MigrationRunner::executePending(
                self::files('one', 'two', 'three'),
                1,
                static function (string $name) use (&$ran): void {
                    $ran[] = $name;
                    if ($name === 'two') {
                        throw new RuntimeException('failure');
                    }
                },
                static function (string $name) use (&$recorded): void {
                    $recorded[] = $name;
                }
            );
            self::fail('Expected migration failure.');
        } catch (MigrationException) {
            self::assertSame(['one', 'two'], $ran);
            self::assertSame(['one'], $recorded);
        }
    }

    public function testRollbackUsesProvidedLatestBatchOrderAndStopsOnFailure(): void
    {
        $records = [
            new MigrationRecord('third', 2, 'now'),
            new MigrationRecord('second', 2, 'now'),
        ];
        $ran = [];
        $deleted = [];
        try {
            MigrationRunner::executeRollback(
                $records,
                static function (string $name) use (&$ran): void {
                    $ran[] = $name;
                    if ($name === 'second') {
                        throw new RuntimeException('failure');
                    }
                },
                static function (string $name) use (&$deleted): void {
                    $deleted[] = $name;
                }
            );
            self::fail('Expected rollback failure.');
        } catch (MigrationException) {
            self::assertSame(['third', 'second'], $ran);
            self::assertSame(['third'], $deleted);
        }
    }

    public function testMissingRollbackFileFailsBeforeHistoryDeletion(): void
    {
        $deleted = [];
        $this->expectException(MigrationException::class);
        try {
            MigrationRunner::executeRollback(
                [new MigrationRecord('missing', 1, 'now')],
                static fn () => throw new MigrationException('Migration file is missing'),
                static function (string $name) use (&$deleted): void {
                    $deleted[] = $name;
                }
            );
        } finally {
            self::assertSame([], $deleted);
        }
    }

    public function testStatusFormattingDisplaysAllStatesAndMetadata(): void
    {
        $ran = MigrationCommand::formatStatus(
            new MigrationStatus('one', 'Ran', 3, '2026-07-26 21:30:00')
        );
        $pending = MigrationCommand::formatStatus(new MigrationStatus('two', 'Pending', null, null));
        $missing = MigrationCommand::formatStatus(
            new MigrationStatus('three', 'Missing file', 2, '2026-07-26 21:00:00')
        );

        self::assertStringContainsString('Ran', $ran);
        self::assertStringContainsString('3', $ran);
        self::assertStringContainsString('2026-07-26 21:30:00', $ran);
        self::assertStringContainsString('Pending', $pending);
        self::assertStringContainsString('Missing file', $missing);
    }

    /**
     * @return list<array{name: string, filename: string, path: string}>
     */
    private static function files(string ...$names): array
    {
        return array_map(
            static fn (string $name): array => [
                'name' => $name,
                'filename' => $name . '.php',
                'path' => '/fixtures/' . $name . '.php',
            ],
            $names
        );
    }
}
