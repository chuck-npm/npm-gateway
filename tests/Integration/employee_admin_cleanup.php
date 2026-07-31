<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
$application=require dirname(__DIR__,2).'/bootstrap/app.php';$applicationConfig=DatabaseProfiles::load('application',$application['root']);$migrationConfig=DatabaseProfiles::load('migration',$application['root']);
if($applicationConfig['database']!=='npmgateway_test'||$migrationConfig['database']!=='npmgateway_test')throw new RuntimeException('Refusing cleanup outside npmgateway_test.');
$db=MySqlConnectionFactory::connect($migrationConfig);try{$db->begin_transaction();foreach(['audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table)$db->query('DELETE FROM `'.$table.'`');$db->commit();echo "npmgateway_test disposable rows cleared.\n";}catch(Throwable $e){$db->rollback();throw $e;}finally{$db->close();}
