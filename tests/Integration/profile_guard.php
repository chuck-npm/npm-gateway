<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
$application=require dirname(__DIR__,2).'/bootstrap/app.php';
foreach(['application','migration'] as $profile){$config=DatabaseProfiles::load($profile,$application['root']);echo $profile.'='.$config['database'].PHP_EOL;if($config['database']!=='npmgateway_test'){fwrite(STDERR,'Refusing database mutation: both profiles must resolve to npmgateway_test.'.PHP_EOL);exit(2);}}
