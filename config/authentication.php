<?php
declare(strict_types=1);
use NpmGateway\Configuration\AuthenticationConfig;
$env=static fn(string $n,string $d=''):string=>(string)($_ENV[$n]??$_SERVER[$n]??getenv($n)?:$d);
$int=static fn(string $n,int $d):int=>(int)$env($n,(string)$d);
$environment=strtolower($env('APP_ENV','production'));
$secure=filter_var($env('SESSION_SECURE','true'),FILTER_VALIDATE_BOOL);
if($environment==='production'&&!$secure) throw new RuntimeException('Production requires secure session cookies.');
return new AuthenticationConfig(
 $env('SESSION_COOKIE_NAME','npm_gateway_session'),$secure,true,$env('SESSION_SAME_SITE','Lax'),
 $int('SESSION_IDLE_MINUTES',60),$int('SESSION_ABSOLUTE_HOURS',8),$int('SESSION_ROTATION_MINUTES',15),
 $int('SESSION_ACTIVITY_WRITE_MINUTES',5),$int('AUTH_MAX_FAILED_ATTEMPTS',5),$int('AUTH_LOCK_MINUTES',15),
 $int('AUTH_IP_FAILURE_LIMIT',10),$int('AUTH_IP_FAILURE_WINDOW_MINUTES',10),$env('APP_KEY')
);
