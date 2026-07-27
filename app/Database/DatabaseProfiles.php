<?php

declare(strict_types=1);

namespace NpmGateway\Database;

use InvalidArgumentException;

final class DatabaseProfiles
{
    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return ['application', 'migration'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function load(string $profile, string $projectRoot): array
    {
        $filename = match ($profile) {
            'application' => 'database.php',
            'migration' => 'migration-database.php',
            default => throw new InvalidArgumentException(
                'Unknown database profile. Expected "application" or "migration".'
            ),
        };

        /** @var array<string, mixed> $config */
        $config = require $projectRoot
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . $filename;

        return $config;
    }
}
