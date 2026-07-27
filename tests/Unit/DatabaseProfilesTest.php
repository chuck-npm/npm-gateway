<?php

declare(strict_types=1);

use NpmGateway\Database\DatabaseProfiles;
use PHPUnit\Framework\TestCase;

final class DatabaseProfilesTest extends TestCase
{
    public function testUnknownProfileIsRejectedBeforeLoadingConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected "application" or "migration"');

        DatabaseProfiles::load('other', dirname(__DIR__, 2));
    }

    public function testApplicationAndMigrationProfilesHaveTheSameShape(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertSame(
            array_keys(DatabaseProfiles::load('application', $root)),
            array_keys(DatabaseProfiles::load('migration', $root))
        );
    }
}
