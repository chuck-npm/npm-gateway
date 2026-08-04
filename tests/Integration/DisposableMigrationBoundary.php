<?php
declare(strict_types=1);
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationRepository;
use NpmGateway\Database\Migration\MigrationRunner;
use NpmGateway\Database\Migration\SchemaVerifier;

final class DisposableMigrationBoundary
{
 private MigrationDiscovery $discovery;
 private MigrationRepository $repository;

 public function __construct(private readonly mysqli $db,private readonly string $root)
 {
  foreach(['application','migration'] as $profile)if(DatabaseProfiles::load($profile,$root)['database']!=='npmgateway_test')throw new RuntimeException('Destructive migration isolation requires npmgateway_test.');
  $this->discovery=new MigrationDiscovery($root.'/database/migrations');$this->repository=new MigrationRepository($db);$this->assertForeignKeys();
 }

 public function atBoundary(string $target,callable $scenario):mixed
 {
  $this->rebuildThrough($target);
  try{return $scenario();}finally{$this->restoreLatest();}
 }

 public function rebuildThrough(string $target):void
 {
  $this->clean();$names=array_column($this->discovery->discover(),'name');$position=array_search($target,$names,true);
  // Migration 009 is the compatibility wrapper for Migration 008 and is rolled down
  // explicitly by that historical test before Migration 008 itself is removed.
  if($target==='202608020008_notifications')$position=array_search('202608020009_company_notices_category',$names,true);
  if($position===false)throw new InvalidArgumentException('Unknown migration boundary.');
  $executed=array_map(static fn($record)=>$record->migration,$this->repository->all());
  for($index=count($names)-1;$index>$position;$index--){$name=$names[$index];if(in_array($name,$executed,true)){$this->discovery->load($name)->down($this->db);$this->repository->delete($name);}}
  $remaining=array_map(static fn($record)=>$record->migration,$this->repository->all());foreach(array_slice($names,0,$position+1) as $name)if(!in_array($name,$remaining,true))throw new RuntimeException("Boundary is missing {$name}.");$this->assertForeignKeys();
 }

 public function restoreLatest():void
 {
  $this->clean();$runner=new MigrationRunner($this->db,$this->repository,$this->discovery);$runner->migrate();(new SchemaVerifier($this->db,$this->repository,$this->discovery,'npmgateway_test'))->verify();foreach($runner->status() as $status)if($status->status!=='Ran')throw new RuntimeException('Disposable schema restoration left a pending migration.');$this->assertForeignKeys();$this->clean();
 }

 public function clean():void
 {
  foreach(['notification_storage_objects','storage_objects','notification_recipients','notifications','user_category_access','audit_logs','employee_property_assignments','user_sessions','login_attempts','properties','users','employees'] as $table){$statement=$this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$statement->bind_param('s',$table);$statement->execute();$exists=$statement->get_result()->fetch_row()!==null;$statement->close();if($exists)$this->db->query("DELETE FROM {$table}");}
 }

 private function assertForeignKeys():void{if((int)$this->db->query('SELECT @@FOREIGN_KEY_CHECKS')->fetch_row()[0]!==1)throw new RuntimeException('Foreign-key checks must remain enabled.');}
}
