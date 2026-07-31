<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
$application=require dirname(__DIR__,2).'/bootstrap/app.php';$connection=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$application['root']));
try{$result=$connection->query("SELECT id,prop_id,property_code,slug FROM properties WHERE prop_id=1 OR property_code='CO' OR slug='corporate'");echo 'matches='.$result->num_rows.PHP_EOL;while($row=$result->fetch_assoc())echo 'conflict id='.(int)$row['id'].' prop_id='.(string)$row['prop_id'].' code='.(string)$row['property_code'].' slug='.(string)$row['slug'].PHP_EOL;foreach(['properties','employees','users','employee_property_assignments','user_sessions','audit_logs'] as $table)echo $table.'='.(int)$connection->query('SELECT COUNT(*) FROM `'.$table.'`')->fetch_row()[0].PHP_EOL;if($result->num_rows!==0)exit(2);}finally{$connection->close();}
