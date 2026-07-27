<?php

declare(strict_types=1);

namespace NpmGateway\Database;

use InvalidArgumentException;
use mysqli;
use RuntimeException;

final class MySqlConnectionFactory
{
    /**
     * @param array{
     *     host?: mixed,
     *     port?: mixed,
     *     database?: mixed,
     *     username?: mixed,
     *     password?: mixed,
     *     ssl_ca?: mixed
     * } $config
     */
    public static function connect(array $config): mysqli
    {
        $validated = self::validate($config);

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = mysqli_init();

        if (!$connection instanceof mysqli) {
            throw new RuntimeException('Unable to initialize MySQLi.');
        }

        $caFile = $validated['ssl_ca'];
        $clientFlags = MYSQLI_CLIENT_SSL;
        if ($caFile !== '') {
            // mysqlnd can return false for this option even though certificate
            // verification is selected by the matching real_connect flag.
            $connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
            $clientFlags |= MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT;

            if (!$connection->ssl_set(null, null, $caFile, null, null)) {
                throw new RuntimeException('Unable to configure the MySQL TLS CA file.');
            }
        }

        $connection->real_connect(
            $validated['host'],
            $validated['username'],
            $validated['password'],
            $validated['database'],
            $validated['port'],
            null,
            $clientFlags
        );
        $connection->set_charset('utf8mb4');

        $result = $connection->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'");
        $tlsStatus = $result->fetch_assoc();
        $result->free();

        if (!is_array($tlsStatus) || ($tlsStatus['Value'] ?? '') === '') {
            $connection->close();
            throw new RuntimeException('MySQL did not negotiate a TLS cipher.');
        }

        return $connection;
    }

    /**
     * @param array<string, mixed> $config
     * @return array{
     *     host: string,
     *     port: int,
     *     database: string,
     *     username: string,
     *     password: string,
     *     ssl_ca: string
     * }
     */
    public static function validate(array $config): array
    {
        foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
                throw new InvalidArgumentException(sprintf('Database configuration "%s" is required.', $key));
            }
        }

        if (!ctype_digit($config['port'])) {
            throw new InvalidArgumentException('Database configuration "port" must be an integer from 1 to 65535.');
        }

        $port = (int) $config['port'];
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Database configuration "port" must be an integer from 1 to 65535.');
        }

        $sslCa = $config['ssl_ca'] ?? '';
        if (!is_string($sslCa)) {
            throw new InvalidArgumentException('Database configuration "ssl_ca" must be a string.');
        }
        if ($sslCa !== '' && (!is_file($sslCa) || !is_readable($sslCa))) {
            throw new RuntimeException('The configured MySQL TLS CA file is missing or unreadable.');
        }

        return [
            'host' => $config['host'],
            'port' => $port,
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'ssl_ca' => $sslCa,
        ];
    }
}
