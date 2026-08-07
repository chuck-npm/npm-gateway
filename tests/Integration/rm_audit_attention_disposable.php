<?php
declare(strict_types=1);
use NpmGateway\Container\ServiceProvider;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Repositories\RmAuditRepository;
use NpmGateway\Support\PublicIdGenerator;
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
foreach(['application','migration'] as $profile)if(DatabaseProfiles::load($profile,$app['root'])['database']!=='npmgateway_test')exit(2);
$container=ServiceProvider::build($app);$db=$container->get(mysqli::class);$ids=new PublicIdGenerator();
$cleanup=static function()use($db):void{
 $auditIds=array_map('intval',array_column($db->query("SELECT id FROM rm_audits WHERE tenant_name LIKE 'Attention Certification%'")->fetch_all(MYSQLI_ASSOC),'id'));
 if($auditIds)$db->query('DELETE FROM rm_audit_history WHERE rm_audit_id IN ('.implode(',',$auditIds).')');
 $db->query("DELETE FROM rm_audits WHERE tenant_name LIKE 'Attention Certification%'");
 $userIds=array_map('intval',array_column($db->query("SELECT id FROM users WHERE username='rmaattentioncert'")->fetch_all(MYSQLI_ASSOC),'id'));
 $employeeIds=array_map('intval',array_column($db->query("SELECT id FROM employees WHERE employee_number='NPM977903'")->fetch_all(MYSQLI_ASSOC),'id'));
 $propertyIds=array_map('intval',array_column($db->query("SELECT id FROM properties WHERE slug LIKE 'rma-attention-%'")->fetch_all(MYSQLI_ASSOC),'id'));
 if($propertyIds)$db->query('DELETE FROM properties WHERE id IN ('.implode(',',$propertyIds).')');
 $users=$userIds?implode(',',$userIds):'0';$db->query("DELETE FROM users WHERE id IN ({$users})");
 if($employeeIds)$db->query('DELETE FROM employees WHERE id IN ('.implode(',',$employeeIds).')');
};
try{
 $cleanup();$employeePublic=$ids->generate();$number='NPM977903';$first='Attention';
 $s=$db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date)VALUES(?,?,'corporate',?,'Certification','Test','active','2026-08-07')");$s->bind_param('sss',$employeePublic,$number,$first);$s->execute();$employeeId=$db->insert_id;
 $userPublic=$ids->generate();$username='rmaattentioncert';$hash=password_hash('Disposable-123!',PASSWORD_DEFAULT);$s=$db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status)VALUES(?,?,?,?,'active')");$s->bind_param('siss',$userPublic,$employeeId,$username,$hash);$s->execute();$userId=$db->insert_id;
 $propertyIds=[];foreach(['pine','other'] as $index=>$suffix){$public=$ids->generate();$slug='rma-attention-'.$suffix;$code=$index===0?'QX':'QY';$name='Attention '.ucfirst($suffix);$email=$suffix.'-attention@example.test';$prop=977910+$index;$s=$db->prepare("INSERT INTO properties(public_id,prop_id,property_code,slug,display_name,status,manager_email,address_line_1,city,state,postal_code,timezone)VALUES(?,?,?,?,?,'active',?,'1 Test Way','Scranton','PA','18503','America/New_York')");$s->bind_param('sissss',$public,$prop,$code,$slug,$name,$email);$s->execute();$propertyIds[]=$db->insert_id;}
 $repo=new RmAuditRepository($db);$at='2026-08-07 12:00:00';
 $make=static fn(int$property,string$name):int=>$repo->insert(['public_id'=>$ids->generate(),'property_id'=>$property,'tenant'=>$name,'unit'=>'1','findings_html'=>'<p>Missing lease</p>','findings_text'=>'Missing lease','actor_id'=>$userId,'at'=>$at]);
 $counts=[$repo->actionableCount($propertyIds[0])];$a=$make($propertyIds[0],'Attention Certification A');$counts[]=$repo->actionableCount($propertyIds[0]);$b=$make($propertyIds[0],'Attention Certification B');$counts[]=$repo->actionableCount($propertyIds[0]);$make($propertyIds[1],'Attention Certification Other');$counts[]=$repo->actionableCount($propertyIds[0]);$repo->transition($a,['open'],'completed',$userId,$at);$counts[]=$repo->actionableCount($propertyIds[0]);$repo->transition($a,['completed'],'returned',$userId,$at);$counts[]=$repo->actionableCount($propertyIds[0]);$repo->transition($a,['returned'],'completed',$userId,$at);$counts[]=$repo->actionableCount($propertyIds[0]);$repo->transition($b,['open'],'completed',$userId,$at);$counts[]=$repo->actionableCount($propertyIds[0]);
 if($counts!==[0,1,2,2,1,2,1,0])throw new RuntimeException('Attention sequence mismatch: '.implode(',',$counts));echo'attention_sequence='.implode(',',$counts)."\nother_property_excluded=yes\n";
}catch(Throwable$error){fwrite(STDERR,$error->getMessage()."\n");$code=1;}finally{$cleanup();$residue=(int)$db->query("SELECT COUNT(*) FROM users WHERE username='rmaattentioncert'")->fetch_row()[0]+(int)$db->query("SELECT COUNT(*) FROM properties WHERE slug LIKE 'rma-attention-%'")->fetch_row()[0];echo"fixture_residue={$residue}\n";$db->close();}exit($code??0);
