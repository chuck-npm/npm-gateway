<?php
declare(strict_types=1);
$env=static fn(string $key):string=>(string)($_ENV[$key]??$_SERVER[$key]??getenv($key)?:'');
return ['host'=>$env('SMTP_HOST'),'port'=>$env('SMTP_PORT'),'username'=>$env('SMTP_USERNAME'),'password'=>$env('SMTP_PASSWORD'),'secure'=>$env('SMTP_SECURE'),'recipients'=>$env('HR_NEW_EMPLOYEE_NOTIFICATION_RECIPIENTS'),'from_address'=>$env('MAIL_FROM_ADDRESS'),'from_name'=>$env('MAIL_FROM_NAME'),'environment'=>$env('APP_ENV')?:'production'];
