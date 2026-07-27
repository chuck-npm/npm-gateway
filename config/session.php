<?php

declare(strict_types=1);

return [
    'name' => getenv('SESSION_NAME') ?: 'npm_gateway_session',
    'secure_cookie' => filter_var(getenv('SESSION_SECURE') ?: true, FILTER_VALIDATE_BOOL),
    'http_only_cookie' => true,
    'same_site' => 'Lax',
    'idle_timeout' => 1800,
    'absolute_timeout' => 28800,
];
