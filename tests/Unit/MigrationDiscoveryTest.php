<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MigrationDiscoveryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'npm_gateway_migrations_' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->directory) ?: [] as $filename) {
            if ($filename !== '.' && $filename !== '..') {
                unlink($this->directory . DIRECTORY_SEPARATOR . $filename);
            }
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testValidFilenameAccepted(): void
    {
        self::assertTrue(MigrationDiscovery::isValidFilename('202607262130_create_users_table.php'));
    }

    #[DataProvider('invalidFilenames')]
    public function testInvalidFilenameRejected(string $filename): void
    {
        self::assertFalse(MigrationDiscovery::isValidFilename($filename));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidFilenames(): iterable
    {
        yield 'short timestamp' => ['20260726213_create_table.php'];
        yield 'missing description' => ['202607262130_.php'];
        yield 'uppercase' => ['202607262130_Create_table.php'];
        yield 'spaces' => ['202607262130_create table.php'];
        yield 'hyphens' => ['202607262130_create-table.php'];
        yield 'wrong extension' => ['202607262130_create_table.sql'];
    }

    public function testDiscoveryIsDeterministicAndIgnoresNonmigrationFiles(): void
    {
        $this->write('202607262132_third.php', self::validMigration());
        $this->write('202607262130_first.php', self::validMigration());
        $this->write('202607262131_second.php', self::validMigration());
        $this->write('README.md', 'documentation');
        $this->write('notes.sql', 'SELECT 1;');
        $this->write('.hidden.php', '<?php return null;');
        $this->write('backup.php.bak', '<?php return null;');

        $names = array_column((new MigrationDiscovery($this->directory))->discover(), 'name');

        self::assertSame([
            '202607262130_first',
            '202607262131_second',
            '202607262132_third',
        ], $names);
    }

    public function testMalformedPhpFilenameFailsClearly(): void
    {
        $this->write('bad-name.php', '<?php return null;');
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Malformed migration filename');

        (new MigrationDiscovery($this->directory))->discover();
    }

    #[DataProvider('invalidReturnValues')]
    public function testInvalidReturnedTypeRejected(string $expression): void
    {
        $this->write('202607262130_invalid.php', "<?php\ndeclare(strict_types=1);\nreturn {$expression};\n");
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('must return MigrationInterface');

        (new MigrationDiscovery($this->directory))->load('202607262130_invalid');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidReturnValues(): iterable
    {
        yield 'null' => ['null'];
        yield 'array' => ['[]'];
        yield 'string' => ["'invalid'"];
        yield 'wrong object' => ['new stdClass()'];
    }

    public function testValidAnonymousMigrationIsLoadedOnlyOnce(): void
    {
        $this->write('202607262130_valid.php', self::validMigration());
        $discovery = new MigrationDiscovery($this->directory);

        $first = $discovery->load('202607262130_valid');
        $second = $discovery->load('202607262130_valid');

        self::assertInstanceOf(MigrationInterface::class, $first);
        self::assertSame($first, $second);
    }

    private function write(string $filename, string $contents): void
    {
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename, $contents);
    }

    private static function validMigration(): string
    {
        return <<<'PHP'
<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface {
    public function up(mysqli $connection): void {}
    public function down(mysqli $connection): void {}
};
PHP;
    }
}
