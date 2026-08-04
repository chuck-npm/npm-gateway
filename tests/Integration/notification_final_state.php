<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
$application=require dirname(__DIR__,2).'/bootstrap/app.php';foreach(['application','migration'] as $profile){$config=DatabaseProfiles::load($profile,$application['root']);if($config['database']!=='npmgateway_test')throw new RuntimeException('Refusing cleanup outside npmgateway_test.');}
$db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$application['root']));$tables=['notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'];
try{$db->begin_transaction();foreach($tables as $table)$db->query("DELETE FROM {$table}");$db->commit();foreach(array_reverse($tables) as $table){$count=(int)$db->query("SELECT COUNT(*) FROM {$table}")->fetch_row()[0];echo $table.'='.$count.PHP_EOL;if($count!==0)throw new RuntimeException("Disposable cleanup failed for {$table}.");}}catch(Throwable $e){$db->rollback();throw $e;}finally{$db->close();}
