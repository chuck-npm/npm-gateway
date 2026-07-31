<?php

declare(strict_types=1);

return [
    '/login' => ['methods' => ['GET', 'POST'], 'controller' => 'authentication'],
    '/logout' => ['methods' => ['POST'], 'controller' => 'authentication'],
    '/dashboard' => ['methods' => ['GET'], 'controller' => 'dashboard', 'middleware' => ['authentication']],
    '/employees' => ['name' => 'employees.index', 'methods' => ['GET'], 'controller' => 'employee-workspace', 'middleware' => ['authentication']],
    '/properties' => ['name'=>'properties.index','methods'=>['GET'],'controller'=>'property-workspace','middleware'=>['authentication']],
    '/human-resources' => ['name'=>'hr.index','methods'=>['GET'],'controller'=>'human-resources','middleware'=>['authentication','corporate-access']],
    '/human-resources/properties' => ['names'=>['GET'=>'hr.properties.index','POST'=>'hr.properties.store'],'methods'=>['GET','POST'],'controller'=>'property-workspace','middleware'=>['authentication','corporate-access']],
    '/human-resources/properties/create' => ['name'=>'hr.properties.create','methods'=>['GET'],'controller'=>'property-workspace','middleware'=>['authentication','corporate-access']],
    '/human-resources/employees' => ['names'=>['GET'=>'hr.employees.index','POST'=>'hr.employees.store'],'methods'=>['GET','POST'],'controller'=>'hr-employee','middleware'=>['authentication','corporate-access']],
    '/human-resources/employees/create' => ['name'=>'hr.employees.create','methods'=>['GET'],'controller'=>'hr-employee','middleware'=>['authentication','corporate-access']],
    '/component-showcase' => [
        'view' => 'pages/component-showcase.php',
        'environments' => ['local', 'development'],
    ],
];
