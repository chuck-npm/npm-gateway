<?php

declare(strict_types=1);

namespace NpmGateway\Database;

use mysqli;
use RuntimeException;

final class DatabaseDiagnostic
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    public static function inspect(string $profile, array $config): array
    {
        if (!in_array($profile, DatabaseProfiles::names(), true)) {
            throw new RuntimeException('Invalid database profile.');
        }

        $expected = MySqlConnectionFactory::validate($config);
        $connection = MySqlConnectionFactory::connect($config);

        try {
            $database = self::scalar($connection, 'SELECT DATABASE()');
            if ($database !== $expected['database']) {
                throw new DatabaseDiagnosticException(
                    'The server selected a database other than the configured database.',
                    self::baseReport($profile, $connection, $database)
                );
            }

            $connectionCharset = $connection->character_set_name();
            $tlsCipher = self::statusValue($connection, 'Ssl_cipher');
            if ($expected['tls_required'] && $tlsCipher === '') {
                throw new DatabaseDiagnosticException(
                    'TLS is not active or no TLS cipher was negotiated.',
                    self::baseReport($profile, $connection, $database)
                );
            }

            [$databaseCharset, $databaseCollation] = self::schemaEncoding($connection, $database);
            $report = [
                ...self::baseReport($profile, $connection, $database),
                'connection_charset' => $connectionCharset,
                'tls_active' => $tlsCipher !== '' ? 'yes' : 'no (permitted local loopback)',
                'tls_cipher' => $tlsCipher !== '' ? $tlsCipher : 'none',
                'database_charset' => $databaseCharset,
                'database_collation' => $databaseCollation,
            ];

            if (strcasecmp($connectionCharset, 'utf8mb4') !== 0) {
                throw new DatabaseDiagnosticException(
                    'The connection character set is not utf8mb4.',
                    $report
                );
            }
            if (strcasecmp($databaseCharset, 'utf8mb4') !== 0) {
                throw new DatabaseDiagnosticException(
                    'The database default character set is not utf8mb4.',
                    $report
                );
            }
            if (!str_starts_with(strtolower($databaseCollation), 'utf8mb4_')) {
                throw new DatabaseDiagnosticException(
                    'The database default collation is not an utf8mb4 collation.',
                    $report
                );
            }

            $grantRows = self::grantRows($connection);
            try {
                if ($profile === 'application') {
                    DatabasePrivilegePolicy::verifyApplication($grantRows, $database);
                } else {
                    DatabasePrivilegePolicy::verifyMigration($grantRows, $database);
                }
            } catch (RuntimeException $exception) {
                $report['privileges'] = 'failed';
                throw new DatabaseDiagnosticException($exception->getMessage(), $report);
            }

            return [
                ...$report,
                'privileges' => $profile === 'application'
                    ? 'CRUD present; schema changes absent'
                    : 'schema changes present',
            ];
        } finally {
            $connection->close();
        }
    }

    /**
     * @return array<string, string>
     */
    private static function baseReport(string $profile, mysqli $connection, string $database): array
    {
        return [
            'profile' => $profile,
            'connection' => 'successful',
            'database' => $database !== '' ? $database : 'none',
            'server_version' => $connection->server_info,
        ];
    }

    private static function scalar(mysqli $connection, string $sql): string
    {
        $result = $connection->query($sql);
        $row = $result->fetch_row();
        $result->free();

        return is_array($row) && is_string($row[0] ?? null) ? $row[0] : '';
    }

    private static function statusValue(mysqli $connection, string $name): string
    {
        if ($name !== 'Ssl_cipher') {
            throw new RuntimeException('Unsupported session status variable.');
        }

        $result = $connection->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'");
        $row = $result->fetch_assoc();
        $result->free();

        return is_array($row) && is_string($row['Value'] ?? null) ? $row['Value'] : '';
    }

    /**
     * @return array{string, string}
     */
    private static function schemaEncoding(mysqli $connection, string $database): array
    {
        $statement = $connection->prepare(
            'SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
             FROM information_schema.SCHEMATA
             WHERE SCHEMA_NAME = ?'
        );
        $statement->bind_param('s', $database);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result->fetch_assoc();
        $statement->close();

        if (!is_array($row)) {
            throw new RuntimeException('Unable to inspect the selected database encoding.');
        }

        return [
            (string) ($row['DEFAULT_CHARACTER_SET_NAME'] ?? ''),
            (string) ($row['DEFAULT_COLLATION_NAME'] ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    private static function grantRows(mysqli $connection): array
    {
        $result = $connection->query('SHOW GRANTS');
        $grantRows = [];

        while ($row = $result->fetch_row()) {
            if (is_string($row[0] ?? null)) {
                $grantRows[] = $row[0];
            }
        }
        $result->free();

        return $grantRows;
    }
}
