<?php

declare(strict_types=1);

namespace NpmGateway\Console;

use NpmGateway\Database\DatabaseDiagnosticException;
use NpmGateway\Database\DatabaseProfiles;
use Throwable;

final class DatabaseCheckCommand
{
    public const USAGE = 'Usage: php bin/gateway database:check <application|migration>';

    /**
     * @param list<string> $arguments
     * @param callable(string): array<string, string> $inspect
     * @param list<string> $secrets
     * @return array{exit_code: int, stdout: string, stderr: string}
     */
    public static function run(array $arguments, callable $inspect, array $secrets = []): array
    {
        $command = $arguments[0] ?? '';
        $profile = $arguments[1] ?? '';

        if (
            count($arguments) !== 2
            || $command !== 'database:check'
            || !in_array($profile, DatabaseProfiles::names(), true)
        ) {
            return ['exit_code' => 2, 'stdout' => '', 'stderr' => self::USAGE . PHP_EOL];
        }

        try {
            $report = $inspect($profile);

            return [
                'exit_code' => 0,
                'stdout' => self::format($report),
                'stderr' => '',
            ];
        } catch (Throwable $exception) {
            $stdout = $exception instanceof DatabaseDiagnosticException
                ? self::format($exception->report())
                : "Profile: {$profile}\nConnection: failed\n";

            return [
                'exit_code' => 1,
                'stdout' => $stdout,
                'stderr' => 'Database check failed: '
                    . self::sanitize($exception->getMessage(), $secrets)
                    . PHP_EOL,
            ];
        }
    }

    /**
     * @param array<string, string> $report
     */
    public static function format(array $report): string
    {
        $labels = [
            'profile' => 'Profile',
            'connection' => 'Connection',
            'database' => 'Selected database',
            'server_version' => 'MySQL server version',
            'connection_charset' => 'Connection character set',
            'tls_active' => 'TLS active',
            'tls_cipher' => 'TLS cipher',
            'database_charset' => 'Database default character set',
            'database_collation' => 'Database default collation',
            'privileges' => 'Privilege check',
        ];
        $output = '';

        foreach ($labels as $key => $label) {
            $output .= sprintf("%s: %s\n", $label, self::singleLine($report[$key] ?? 'unavailable'));
        }

        return $output;
    }

    /**
     * @param list<string> $secrets
     */
    private static function sanitize(string $message, array $secrets): string
    {
        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $message = str_replace($secret, '[redacted]', $message);
            }
        }

        return self::singleLine($message);
    }

    private static function singleLine(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }
}
