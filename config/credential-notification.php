<?php
declare(strict_types=1);
$env = static fn (string $name): string => (string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: '');
$driver = strtolower($env('GATEWAY_CREDENTIAL_NOTICE_DRIVER'));
return [
    'environment' => $env('APP_ENV') ?: 'production',
    'driver' => $driver,
    // No approved production transport adapter ships in this commit.
    'configured' => false,
    'allow_local_fallback' => filter_var($env('GATEWAY_CREDENTIAL_NOTICE_ALLOW_LOCAL_FALLBACK'), FILTER_VALIDATE_BOOL),
    'recipient_email' => $env('GATEWAY_CREDENTIAL_NOTICE_TO_EMAIL'),
    'recipient_name' => $env('GATEWAY_CREDENTIAL_NOTICE_TO_NAME'),
    'subject' => $env('GATEWAY_CREDENTIAL_NOTICE_SUBJECT') ?: 'secure - NPM Gateway User Credentials',
];
