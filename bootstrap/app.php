<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (!is_file($autoloadPath)) {
    throw new RuntimeException(
        'Composer dependencies are not installed. Run "composer install" from the project root.'
    );
}

require $autoloadPath;

if (class_exists(\Dotenv\Dotenv::class) && is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
    \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

$appConfig = require $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
$timezone = $appConfig['timezone'] ?? 'UTC';

if (is_string($timezone) && $timezone !== '') {
    date_default_timezone_set($timezone);
}

return [
    'root' => $projectRoot,
    'config' => [
        'app' => $appConfig,
    ],
];
