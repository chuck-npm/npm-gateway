<?php

declare(strict_types=1);

return [
    'name' => (string) ($_ENV['APP_NAME'] ?? 'NPM Gateway'),

    'environment' => (string) ($_ENV['APP_ENV'] ?? 'production'),

    'debug' => filter_var(
        $_ENV['APP_DEBUG'] ?? false,
        FILTER_VALIDATE_BOOL
    ),

    'url' => (string) ($_ENV['APP_URL'] ?? ''),

    'timezone' => (string) (
        $_ENV['APP_TIMEZONE']
        ?? 'America/New_York'
    ),
];