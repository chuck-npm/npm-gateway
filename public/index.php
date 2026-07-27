<?php

declare(strict_types=1);

use NpmGateway\Http\Request\RouteMatcher;

$application = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
$projectRoot = $application['root'];
$appConfig = $application['config']['app'];
$applicationName = (string) ($appConfig['name'] ?? 'NPM Gateway');
$environment = (string) ($appConfig['environment'] ?? 'production');
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rtrim($requestPath, '/') : '/';
$requestPath = $requestPath === '' ? '/' : $requestPath;
$responseStatus = 200;

header('Content-Type: text/html; charset=UTF-8');

if ($requestPath === '/') {
    $pageTitle = $applicationName;
    $navbarItems = [];
    $navbarUserLabel = 'User menu';
    $contentHtml = sprintf(
        '<section class="gateway-panel"><span class="gateway-eyebrow">Internal company portal</span>'
        . '<h1 class="gateway-title">%s</h1><p class="gateway-lead">Application foundation loaded successfully.</p></section>',
        htmlspecialchars($applicationName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
    );

    require $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
        . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'app.php';
    return;
}

$routes = require $projectRoot . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';

$route = (new RouteMatcher())->match($requestPath, $environment, $routes);

if ($route === null) {
    $responseStatus = 404;
    http_response_code($responseStatus);
    require $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
        . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';
    return;
}

$viewPath = $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $route['view']);

ob_start();
require $viewPath;
$contentHtml = (string) ob_get_clean();

require $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'app.php';
