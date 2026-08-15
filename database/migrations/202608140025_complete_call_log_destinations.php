<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\{MigrationException,MigrationInterface};
return new class implements MigrationInterface
{
 private const OWNED=[
  ['BT','Boulder Trails','+12293544477','01K2NPMCALLLOGBOULDER001XX'],
  ['CF','Crumley Farms','+12295205722','01K2NPMCALLLOGCRUMLEY001XX'],
  ['FF','Flamingo Flats','+16206807080','01K2NPMCALLLOGFLAMINGO01XX'],
  ['HR','Highridge','+13342038030','01K2NPMCALLLOGHIGHRIDGE01X'],
  ['MW','Maplewind','+17858027085','01K2NPMCALLLOGMAPLEWIND01X'],
  ['PP','Pearce Pointe','+12292317979','01K2NPMCALLLOGPEARCE001XXX'],
  ['PH','Pine Hill','+12292317090','01K2NPMCALLLOGPINEHILL001X'],
  ['WP','Wunderpark','+12292317927','01K2NPMCALLLOGWUNDER001XXX'],
 ];
 private const EXTERNAL=['+12297928075','Suburban','01K2NPMCALLLOGSUBURBAN001X'];
 private const INSERTED_PUBLIC_IDS=['01K2NPMCALLLOGBOULDER001XX','01K2NPMCALLLOGCRUMLEY001XX','01K2NPMCALLLOGFLAMINGO01XX','01K2NPMCALLLOGMAPLEWIND01X','01K2NPMCALLLOGPEARCE001XXX','01K2NPMCALLLOGWUNDER001XXX'];
 public function up(mysqli$db):void
 {
  if($this->table($db,'call_log_destinations')!==1)throw new MigrationException('call_log_destinations is missing.');$now=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
  foreach(self::OWNED as[$code,$name,$tn,$public]){$property=$this->property($db,$code,$name,$tn);$existing=$this->destination($db,$tn);if($existing!==null){if((int)$existing['property_id']!==$property||(string)($existing['external_display_name']??'')!==''||(int)$existing['active']!==1)throw new MigrationException("Called TN {$tn} has an incorrect existing Call Log destination.");continue;}$s=$db->prepare('INSERT INTO call_log_destinations(public_id,called_tn,property_id,external_display_name,active,created_at,updated_at) VALUES(?,?,?,NULL,1,?,?)');$s->bind_param('ssiss',$public,$tn,$property,$now,$now);$s->execute();$s->close();}
  [$tn,$name,$public]=self::EXTERNAL;$existing=$this->destination($db,$tn);if($existing!==null){if($existing['property_id']!==null||(string)$existing['external_display_name']!==$name||(int)$existing['active']!==1)throw new MigrationException("Called TN {$tn} has an incorrect existing Call Log destination.");}else{$s=$db->prepare('INSERT INTO call_log_destinations(public_id,called_tn,property_id,external_display_name,active,created_at,updated_at) VALUES(?,?,NULL,?,1,?,?)');$s->bind_param('sssss',$public,$tn,$name,$now,$now);$s->execute();$s->close();}
 }
 public function down(mysqli$db):void
 {
  foreach(self::INSERTED_PUBLIC_IDS as$public){$s=$db->prepare('SELECT id FROM call_log_destinations WHERE public_id=?');$s->bind_param('s',$public);$s->execute();$id=(int)($s->get_result()->fetch_row()[0]??0);$s->close();if($id===0)continue;$s=$db->prepare('SELECT COUNT(*) FROM call_logs WHERE destination_id=?');$s->bind_param('i',$id);$s->execute();$calls=(int)$s->get_result()->fetch_row()[0];$s->close();if($calls>0)throw new MigrationException('Cannot roll back completed Call Log destinations while calls use them.');$s=$db->prepare('DELETE FROM call_log_destinations WHERE id=?');$s->bind_param('i',$id);$s->execute();$s->close();}
 }
 private function property(mysqli$db,string$code,string$name,string$tn):int{$s=$db->prepare('SELECT id,display_name,ivr_number FROM properties WHERE property_code=?');$s->bind_param('s',$code);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();if(count($rows)!==1||(string)$rows[0]['display_name']!==$name||(string)$rows[0]['ivr_number']!==$tn)throw new MigrationException("Property {$code} is missing or conflicts with the approved Call Log mapping.");return(int)$rows[0]['id'];}
 private function destination(mysqli$db,string$tn):?array{$s=$db->prepare('SELECT property_id,external_display_name,active FROM call_log_destinations WHERE called_tn=?');$s->bind_param('s',$tn);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();return$row?:null;}
 private function table(mysqli$db,string$table):int{$s=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->bind_param('s',$table);$s->execute();$count=(int)$s->get_result()->fetch_row()[0];$s->close();return$count;}
};
