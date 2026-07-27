<?php

declare(strict_types=1);

$env = static function (string $name): string {
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

    return is_string($value) ? $value : '';
};

return [
    'host' => $env('MIGRATION_DB_HOST'),
    'port' => $env('MIGRATION_DB_PORT'),
    'database' => $env('MIGRATION_DB_NAME'),
    'username' => $env('MIGRATION_DB_USER'),
    'password' => $env('MIGRATION_DB_PASSWORD'),
    'ssl_ca' => $env('MIGRATION_DB_SSL_CA'),
    'app_env' => $env('APP_ENV'),
];
