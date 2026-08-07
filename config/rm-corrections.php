<?php
declare(strict_types=1);
$env=static fn(string $key):string=>(string)($_ENV[$key]??$_SERVER[$key]??getenv($key)?:'');
return ['test_mode'=>filter_var($env('RM_CORRECTIONS_TEST_MODE'),FILTER_VALIDATE_BOOL),'test_email'=>trim($env('RM_CORRECTIONS_TEST_EMAIL')),'reviewer_email'=>trim($env('RM_CORRECTIONS_REVIEWER_EMAIL')),'app_url'=>rtrim($env('APP_URL'),'/'),'smtp'=>require __DIR__.'/hr-employee-notification.php'];
