<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
$application=require dirname(__DIR__,2).'/bootstrap/app.php';$config=DatabaseProfiles::load('migration',$application['root']);$connection=MySqlConnectionFactory::connect($config);$data=[];
try{$tables=$connection->query('SHOW TABLES');while($row=$tables->fetch_row()){$table=(string)$row[0];$quoted='`'.str_replace('`','``',$table).'`';$create=$connection->query('SHOW CREATE TABLE '.$quoted)->fetch_row();$count=$connection->query('SELECT COUNT(*) FROM '.$quoted)->fetch_row();$data[$table]=['create'=>(string)$create[1],'rows'=>(int)$count[0]];}ksort($data);echo $config['database'].' '.hash('sha256',json_encode($data,JSON_THROW_ON_ERROR)).PHP_EOL;}finally{$connection->close();}
