<?php
declare(strict_types=1);
use NpmGateway\Database\{DatabaseProfiles,MySqlConnectionFactory};
use NpmGateway\Support\PublicIdGenerator;

$app=require dirname(__DIR__,2).'/bootstrap/app.php';
foreach(['application','migration']as$profile)if(DatabaseProfiles::load($profile,$app['root'])['database']!=='npmgateway_test'){fwrite(STDERR,"Shared property contacts certification requires npmgateway_test.\n");exit(2);}
$db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$app['root']));$ids=new PublicIdGenerator();$prefix='sister-cert-';
$cleanup=static function(mysqli$db,string$prefix):void{$s=$db->prepare('DELETE FROM properties WHERE slug LIKE ?');$like=$prefix.'%';$s->bind_param('s',$like);$s->execute();$s->close();};
$insert=static function(mysqli$db,string$public,int$prop,string$code,string$slug,string$name,string$email,string$office,string$ivr):int{$s=$db->prepare("INSERT INTO properties(public_id,prop_id,property_code,slug,display_name,status,office_phone,manager_email,ivr_number,ivr_routing_email,address_line_1,city,state,postal_code,timezone) VALUES(?,?,?,?,?,'active',?,?,?,?,'1 Certification Way','Testville','GA','31904','America/New_York')");$routing=$prop.'@rentertext.example.test';$s->bind_param('sisssssss',$public,$prop,$code,$slug,$name,$office,$email,$ivr,$routing);$s->execute();$id=(int)$db->insert_id;$s->close();return$id;};
$duplicateFails=static function(callable$operation):void{try{$operation();throw new RuntimeException('Duplicate identity unexpectedly persisted.');}catch(mysqli_sql_exception$e){if($e->getCode()!==1062)throw$e;}};
try{
 $indexes=array_map(static fn(array$row):array=>['INDEX_NAME'=>(string)$row['INDEX_NAME'],'COLUMN_NAME'=>(string)$row['COLUMN_NAME'],'NON_UNIQUE'=>(int)$row['NON_UNIQUE']],$db->query("SELECT INDEX_NAME,COLUMN_NAME,NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='properties' AND COLUMN_NAME IN ('manager_email','office_phone','ivr_number') ORDER BY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC));
 if($indexes!==[['INDEX_NAME'=>'idx_properties_ivr_number','COLUMN_NAME'=>'ivr_number','NON_UNIQUE'=>1],['INDEX_NAME'=>'idx_properties_manager_email','COLUMN_NAME'=>'manager_email','NON_UNIQUE'=>1]])throw new RuntimeException('Shared contact indexes do not match the certified schema.');
 $cleanup($db,$prefix);$emailA='manager@example.com';$phoneA='+15551234567';$emailB='manager-two@example.com';$phoneB='+15557654321';
 $pinePublic=$ids->generate();$pine=$insert($db,$pinePublic,990101,'PA',$prefix.'pine-hill','Pine Hill Certification',$emailA,$phoneA,'+15550000101');
 $manorPublic=$ids->generate();$manor=$insert($db,$manorPublic,990102,'PM',$prefix.'pine-manor','Pine Manor Certification',$emailA,$phoneA,'+15550000101');
 $highridgePublic=$ids->generate();$highridge=$insert($db,$highridgePublic,990103,'HR',$prefix.'highridge','Highridge Certification',$emailB,$phoneB,'+15550000103');
 $sizemorePublic=$ids->generate();$sizemore=$insert($db,$sizemorePublic,990104,'SZ',$prefix.'sizemore','Sizemore Certification',$emailB,$phoneB,'+15550000103');
 if((int)$db->query("SELECT COUNT(*) FROM properties WHERE slug LIKE '{$prefix}%'")->fetch_row()[0]!==4)throw new RuntimeException('Four sister-park fixtures did not persist.');
 $insert($db,$ids->generate(),990105,'EO',$prefix.'email-only','Email Only Certification',$emailA,'+15550000901','+15550000105');
 $insert($db,$ids->generate(),990106,'PO',$prefix.'phone-only','Phone Only Certification','phone-only@example.test',$phoneA,'+15550000106');
 $insert($db,$ids->generate(),990107,'BO',$prefix.'both','Both Certification',$emailA,$phoneA,'+15550000107');
 $sharedIvr='+15550000101';$s=$db->prepare('UPDATE properties SET manager_email=?,office_phone=?,ivr_number=? WHERE id=?');$s->bind_param('sssi',$emailA,$phoneA,$sharedIvr,$sizemore);$s->execute();$s->close();
 $duplicateFails(fn()=> $insert($db,$pinePublic,990108,'DU',$prefix.'duplicate-public','Duplicate Public',$emailA,$phoneA,'+15550000108'));
 $duplicateFails(fn()=> $insert($db,$ids->generate(),990109,'PA',$prefix.'duplicate-code','Duplicate Code',$emailA,$phoneA,'+15550000109'));
 $duplicateFails(fn()=> $insert($db,$ids->generate(),990110,'DS',$prefix.'pine-hill','Duplicate Slug',$emailA,$phoneA,'+15550000110'));
 $rows=$db->query("SELECT slug,manager_email,office_phone FROM properties WHERE slug LIKE '{$prefix}%' ORDER BY prop_id")->fetch_all(MYSQLI_ASSOC);
 if(count($rows)!==7||$rows[0]['slug']!==$prefix.'pine-hill'||$rows[1]['slug']!==$prefix.'pine-manor')throw new RuntimeException('Property lookup/order failed.');
 echo "profiles=npmgateway_test\nmanager_email_index=idx_properties_manager_email_non_unique\noffice_phone_index=none\nivr_number_index=idx_properties_ivr_number_non_unique\nsister_parks=4_persisted\nshared_email=passed\nshared_office_phone=passed\nshared_ivr_number=passed\nshared_all_contacts=passed\nupdate_to_shared_contacts=passed\npublic_id_unique=preserved\nproperty_code_unique=preserved\nslug_unique=preserved\nlookup_filtering=passed\n";
}finally{$cleanup($db,$prefix);$residue=(int)$db->query("SELECT COUNT(*) FROM properties WHERE slug LIKE '{$prefix}%'")->fetch_row()[0];echo 'fixture_residue='.$residue."\n";$db->close();}
if($residue!==0)exit(1);
