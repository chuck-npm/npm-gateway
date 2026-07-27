<?php

declare(strict_types=1);

namespace NpmGateway\Database;

use RuntimeException;

final class DatabasePrivilegePolicy
{
    private const CRUD_PRIVILEGES = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
    private const UNSAFE_APPLICATION_PRIVILEGES = [
        'CREATE',
        'ALTER',
        'DROP',
        'INDEX',
        'REFERENCES',
        'CREATE VIEW',
        'SHOW VIEW',
        'TRIGGER',
        'EVENT',
        'EXECUTE',
        'CREATE ROUTINE',
        'ALTER ROUTINE',
        'CREATE TEMPORARY TABLES',
        'LOCK TABLES',
        'GRANT OPTION',
        'ALL PRIVILEGES',
    ];
    private const REQUIRED_MIGRATION_PRIVILEGES = ['CREATE', 'ALTER', 'DROP'];

    /**
     * @param list<string> $grantRows
     */
    public static function verifyApplication(array $grantRows, string $database): void
    {
        $privileges = self::privilegesForDatabase($grantRows, $database);
        $missingCrud = array_diff(self::CRUD_PRIVILEGES, $privileges);
        $unsafe = array_intersect(self::UNSAFE_APPLICATION_PRIVILEGES, $privileges);

        if ($missingCrud !== [] || $unsafe !== []) {
            throw new RuntimeException(
                sprintf(
                    'Application privileges are unsafe (missing CRUD: %s; unsafe grants: %s).',
                    $missingCrud === [] ? 'none' : implode(', ', $missingCrud),
                    $unsafe === [] ? 'none' : implode(', ', $unsafe)
                )
            );
        }
    }

    /**
     * @param list<string> $grantRows
     */
    public static function verifyMigration(array $grantRows, string $database): void
    {
        $privileges = self::privilegesForDatabase($grantRows, $database);

        if (
            !in_array('ALL PRIVILEGES', $privileges, true)
            && array_diff(self::REQUIRED_MIGRATION_PRIVILEGES, $privileges) !== []
        ) {
            throw new RuntimeException(
                'Migration privileges are insufficient: CREATE, ALTER, and DROP are required.'
            );
        }
    }

    /**
     * @param list<string> $grantRows
     * @return list<string>
     */
    public static function privilegesForDatabase(array $grantRows, string $database): array
    {
        $privileges = [];
        $normalizedDatabase = strtolower(self::unquote($database));

        foreach ($grantRows as $grant) {
            $normalizedGrant = preg_replace('/\s+/', ' ', trim($grant)) ?? '';
            if (!preg_match('/^GRANT\s+(.+?)\s+ON\s+(.+?)\s+TO\s+/i', $normalizedGrant, $matches)) {
                continue;
            }

            $scope = strtolower(str_replace(' ', '', self::unquote($matches[2])));
            if ($scope !== '*.*' && $scope !== $normalizedDatabase . '.*') {
                continue;
            }

            foreach (explode(',', $matches[1]) as $listedPrivilege) {
                $privilege = preg_replace('/\s*\([^)]*\)\s*$/', '', trim($listedPrivilege));
                $privilege = strtoupper(preg_replace('/\s+/', ' ', $privilege ?? '') ?? '');

                // USAGE is deliberately neutral. Other harmless metadata
                // privileges are retained but ignored by the policy lists.
                if ($privilege !== '' && $privilege !== 'USAGE') {
                    $privileges[] = $privilege;
                }
            }

            if (preg_match('/\s+WITH\s+GRANT\s+OPTION\s*$/i', $normalizedGrant)) {
                $privileges[] = 'GRANT OPTION';
            }
        }

        return array_values(array_unique($privileges));
    }

    private static function unquote(string $value): string
    {
        return str_replace(['`', "'", '"'], '', trim($value));
    }
}
