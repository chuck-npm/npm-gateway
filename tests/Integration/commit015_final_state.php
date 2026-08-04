<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
$application=require dirname(__DIR__,2).'/bootstrap/app.php';
foreach(['application','migration'] as $profile){$resolved=DatabaseProfiles::load($profile,$application['root']);if(($resolved['database']??'')!=='npmgateway_test'){fwrite(STDERR,"Cleanup blocked: {$profile} is not npmgateway_test.\n");exit(2);}}
$db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$application['root']));
$tables=['notification_storage_objects','storage_objects','notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'];
try{$db->begin_transaction();foreach($tables as $table)$db->query("DELETE FROM {$table}");$db->commit();foreach($tables as $table){$count=(int)$db->query("SELECT COUNT(*) FROM {$table}")->fetch_row()[0];echo "{$table}={$count}\n";if($count!==0)throw new RuntimeException("Residue remains in {$table}.");}}catch(Throwable $e){$db->rollback();fwrite(STDERR,str_replace(["\r","\n"],' ',$e->getMessage())."\n");exit(1);}finally{$db->close();}
