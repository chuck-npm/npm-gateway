<?php
declare(strict_types=1);
$env=static function(string $key):string{$value=$_ENV[$key]??$_SERVER[$key]??getenv($key);return is_string($value)?trim($value):'';};
return ['user_public_id'=>$env('PROTECTED_PRINCIPAL_USER_PUBLIC_ID'),'employee_public_id'=>$env('PROTECTED_PRINCIPAL_EMPLOYEE_PUBLIC_ID'),'required_categories'=>$env('PROTECTED_PRINCIPAL_REQUIRED_CATEGORIES')?:'admin'];
