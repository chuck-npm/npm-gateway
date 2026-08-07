<?php
declare(strict_types=1);
$env=static fn(string $key):string=>(string)($_ENV[$key]??$_SERVER[$key]??getenv($key)?:'');
return ['reviewer_email'=>trim($env('RM_AUDIT_REVIEWER_EMAIL')),'corporate_test_mode'=>filter_var($env('RM_AUDIT_CORPORATE_TEST_MODE'),FILTER_VALIDATE_BOOL),'corporate_test_email'=>trim($env('RM_AUDIT_CORPORATE_TEST_EMAIL')),'app_url'=>rtrim($env('APP_URL'),'/'),'smtp'=>require __DIR__.'/hr-employee-notification.php'];
