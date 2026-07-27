<?php

declare(strict_types=1);

$env = static function (string $name): string {
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

    return is_string($value) ? $value : '';
};

return [
    'host' => $env('DB_HOST'),
    'port' => $env('DB_PORT'),
    'database' => $env('DB_NAME'),
    'username' => $env('DB_USER'),
    'password' => $env('DB_PASSWORD'),
    'ssl_ca' => $env('DB_SSL_CA'),
    'app_env' => $env('APP_ENV'),
];
