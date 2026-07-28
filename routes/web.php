<?php

declare(strict_types=1);

return [
    '/login' => ['methods' => ['GET', 'POST'], 'controller' => 'authentication'],
    '/logout' => ['methods' => ['POST'], 'controller' => 'authentication'],
    '/dashboard' => ['methods' => ['GET'], 'controller' => 'dashboard', 'middleware' => ['authentication']],
    '/employees' => ['name' => 'employees.index', 'methods' => ['GET'], 'controller' => 'employee-workspace', 'middleware' => ['authentication']],
    '/employees/{publicId}' => ['name' => 'employees.show', 'methods' => ['GET'], 'controller' => 'employee-workspace', 'middleware' => ['authentication']],
    '/component-showcase' => [
        'view' => 'pages/component-showcase.php',
        'environments' => ['local', 'development'],
    ],
];
