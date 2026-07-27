<?php

declare(strict_types=1);

use NpmGateway\Database\DatabasePrivilegePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DatabasePrivilegePolicyTest extends TestCase
{
    private const MYSQL_84_CRUD_GRANTS = [
        'GRANT USAGE ON *.* TO `npmgateway_web`@`%`',
        'GRANT SELECT, INSERT, UPDATE, DELETE ON `npmgateway`.* TO `npmgateway_web`@`%`',
    ];

    public function testMysql84UsageAndCrudRowsPassApplicationPolicy(): void
    {
        DatabasePrivilegePolicy::verifyApplication(self::MYSQL_84_CRUD_GRANTS, 'npmgateway');

        self::addToAssertionCount(1);
    }

    public function testUsageIsNeutral(): void
    {
        $privileges = DatabasePrivilegePolicy::privilegesForDatabase(
            ['GRANT USAGE ON *.* TO `npmgateway_web`@`%`'],
            'npmgateway'
        );

        self::assertSame([], $privileges);
    }

    public function testCrudOrderingWhitespaceAndCaseDoNotMatter(): void
    {
        DatabasePrivilegePolicy::verifyApplication([
            ' grant   delete, update,   SELECT, insert ON `npmgateway`.* TO `npmgateway_web`@`%` ',
        ], 'npmgateway');

        self::addToAssertionCount(1);
    }

    /**
     * @param list<string> $grants
     */
    #[DataProvider('quotedSchemaGrants')]
    public function testQuotedAndUnquotedSchemaNamesParseCorrectly(array $grants): void
    {
        DatabasePrivilegePolicy::verifyApplication($grants, 'npmgateway');

        self::addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function quotedSchemaGrants(): iterable
    {
        foreach (['`npmgateway`.*', '"npmgateway".*', "'npmgateway'.*", 'npmgateway.*'] as $scope) {
            yield $scope => [[
                "GRANT SELECT, INSERT, UPDATE, DELETE ON {$scope} TO 'npmgateway_web'@'%'",
            ]];
        }
    }

    public function testMultipleRowsAndGlobalCrudAreCombined(): void
    {
        DatabasePrivilegePolicy::verifyApplication([
            'GRANT USAGE ON *.* TO `npmgateway_web`@`%`',
            'GRANT DELETE, SELECT ON *.* TO `npmgateway_web`@`%`',
            'GRANT UPDATE ON `npmgateway`.* TO `npmgateway_web`@`%`',
            'GRANT INSERT ON "npmgateway".* TO "npmgateway_web"@"%"',
            'GRANT CREATE ON `another_database`.* TO `npmgateway_web`@`%`',
        ], 'npmgateway');

        self::addToAssertionCount(1);
    }

    #[DataProvider('unsafePrivileges')]
    public function testSchemaChangingPrivilegeCausesFailure(string $privilege, string $scope): void
    {
        $this->expectException(RuntimeException::class);

        DatabasePrivilegePolicy::verifyApplication([
            ...self::MYSQL_84_CRUD_GRANTS,
            "GRANT {$privilege} ON {$scope} TO `npmgateway_web`@`%`",
        ], 'npmgateway');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafePrivileges(): iterable
    {
        yield 'schema CREATE' => ['CREATE', '`npmgateway`.*'];
        yield 'global ALTER' => ['ALTER', '*.*'];
    }

    public function testGrantOptionCausesFailure(): void
    {
        $this->expectException(RuntimeException::class);

        DatabasePrivilegePolicy::verifyApplication([
            ...self::MYSQL_84_CRUD_GRANTS,
            'GRANT SELECT ON `npmgateway`.* TO `npmgateway_web`@`%` WITH GRANT OPTION',
        ], 'npmgateway');
    }

    public function testMissingOneCrudPrivilegeCausesFailure(): void
    {
        $this->expectException(RuntimeException::class);

        DatabasePrivilegePolicy::verifyApplication([
            'GRANT SELECT, INSERT, UPDATE ON `npmgateway`.* TO `npmgateway_web`@`%`',
        ], 'npmgateway');
    }

    public function testHarmlessMetadataPrivilegeIsNotUnsafe(): void
    {
        DatabasePrivilegePolicy::verifyApplication([
            ...self::MYSQL_84_CRUD_GRANTS,
            'GRANT SHOW DATABASES ON *.* TO `npmgateway_web`@`%`',
        ], 'npmgateway');

        self::addToAssertionCount(1);
    }
}
