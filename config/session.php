<?php

declare(strict_types=1);

$env = static fn (string $name, string $default): string => (string) (
    $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: $default
);

return [
    'name' => $env('NATIVE_SESSION_COOKIE_NAME', 'npm_gateway_ui_state'),
    'secure_cookie' => filter_var($env('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOL),
    'http_only_cookie' => true,
    'same_site' => 'Lax',
    'idle_timeout' => 3600,
    'absolute_timeout' => 28800,
];
