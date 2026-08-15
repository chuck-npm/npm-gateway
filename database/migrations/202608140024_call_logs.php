<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\{MigrationException,MigrationInterface};
return new class implements MigrationInterface
{
 public function up(mysqli $db):void
 {
  foreach(['properties','users'] as $table)if($this->tables($db,$table)!==1)throw new MigrationException("Required table {$table} is missing.");
  foreach(['call_log_destinations','call_log_imports','call_logs'] as $table)if($this->tables($db,$table)!==0)throw new MigrationException("{$table} already exists.");
  $db->query("CREATE TABLE call_log_destinations (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,public_id CHAR(26) NOT NULL,called_tn VARCHAR(16) NOT NULL,property_id BIGINT UNSIGNED NULL,external_display_name VARCHAR(100) NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_call_log_destinations_public_id(public_id),UNIQUE KEY uq_call_log_destinations_called_tn(called_tn),KEY idx_call_log_destinations_property(property_id),CONSTRAINT fk_call_log_destinations_property FOREIGN KEY(property_id) REFERENCES properties(id) ON UPDATE RESTRICT ON DELETE RESTRICT,CONSTRAINT chk_call_log_destinations_identity CHECK((property_id IS NOT NULL AND external_display_name IS NULL) OR (property_id IS NULL AND external_display_name IS NOT NULL)),CONSTRAINT chk_call_log_destinations_active CHECK(active IN (0,1)),CONSTRAINT chk_call_log_destinations_tn CHECK(called_tn REGEXP '^[+]1[0-9]{10}$')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
  $db->query("CREATE TABLE call_log_imports (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,public_id CHAR(26) NOT NULL,original_filename VARCHAR(255) NOT NULL,file_sha256 CHAR(64) NOT NULL,uploaded_by_user_id BIGINT UNSIGNED NOT NULL,source_row_count INT UNSIGNED NOT NULL,imported_row_count INT UNSIGNED NOT NULL,source_started_at DATETIME(3) NOT NULL,source_ended_at DATETIME(3) NOT NULL,imported_at DATETIME NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_call_log_imports_public_id(public_id),UNIQUE KEY uq_call_log_imports_file_sha256(file_sha256),KEY idx_call_log_imports_uploader(uploaded_by_user_id),KEY idx_call_log_imports_imported_at(imported_at),CONSTRAINT fk_call_log_imports_uploader FOREIGN KEY(uploaded_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,CONSTRAINT chk_call_log_imports_counts CHECK(source_row_count=imported_row_count)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
  $db->query("CREATE TABLE call_logs (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,public_id CHAR(26) NOT NULL,import_id BIGINT UNSIGNED NOT NULL,destination_id BIGINT UNSIGNED NOT NULL,calling_tn VARCHAR(16) NOT NULL,called_tn VARCHAR(16) NOT NULL,started_at DATETIME(3) NOT NULL,released_at DATETIME(3) NOT NULL,call_duration_seconds DECIMAL(12,3) UNSIGNED NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_call_logs_public_id(public_id),KEY idx_call_logs_started(started_at,id),KEY idx_call_logs_destination_started(destination_id,started_at,id),KEY idx_call_logs_called_started(called_tn,started_at,id),KEY idx_call_logs_import(import_id),CONSTRAINT fk_call_logs_import FOREIGN KEY(import_id) REFERENCES call_log_imports(id) ON UPDATE RESTRICT ON DELETE RESTRICT,CONSTRAINT fk_call_logs_destination FOREIGN KEY(destination_id) REFERENCES call_log_destinations(id) ON UPDATE RESTRICT ON DELETE RESTRICT,CONSTRAINT chk_call_logs_times CHECK(released_at>=started_at),CONSTRAINT chk_call_logs_calling_tn CHECK(calling_tn REGEXP '^[+]1[0-9]{10}$'),CONSTRAINT chk_call_logs_called_tn CHECK(called_tn REGEXP '^[+]1[0-9]{10}$')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
  $now=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
  $this->propertyDestination($db,'01K2NPMCALLLOGPINEHILL001X','+12292317090','Pine Hill',$now);
  $this->propertyDestination($db,'01K2NPMCALLLOGHIGHRIDGE01X','+13342038030','Highridge',$now);
  $s=$db->prepare('INSERT INTO call_log_destinations(public_id,called_tn,property_id,external_display_name,active,created_at,updated_at) VALUES(?,?,NULL,?,1,?,?)');$public='01K2NPMCALLLOGSUBURBAN001X';$tn='+12297928075';$name='Suburban';$s->bind_param('sssss',$public,$tn,$name,$now,$now);$s->execute();$s->close();
 }
 public function down(mysqli $db):void
 {
  if($this->tables($db,'call_logs')===1&&(int)$db->query('SELECT COUNT(*) FROM call_logs')->fetch_row()[0]>0)throw new MigrationException('Cannot roll back Call Logs while imported call records exist.');
  $db->query('DROP TABLE IF EXISTS call_logs');$db->query('DROP TABLE IF EXISTS call_log_imports');$db->query('DROP TABLE IF EXISTS call_log_destinations');
 }
 private function propertyDestination(mysqli$db,string$public,string$tn,string$name,string$now):void
 {
  $s=$db->prepare('SELECT id,ivr_number FROM properties WHERE display_name=?');$s->bind_param('s',$name);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();if(count($rows)!==1||(string)$rows[0]['ivr_number']!==$tn)throw new MigrationException("Call Log destination {$name} is missing or has an unexpected IVR.");$property=(int)$rows[0]['id'];
  $s=$db->prepare('INSERT INTO call_log_destinations(public_id,called_tn,property_id,external_display_name,active,created_at,updated_at) VALUES(?,?,?,NULL,1,?,?)');$s->bind_param('ssiss',$public,$tn,$property,$now,$now);$s->execute();$s->close();
 }
 private function tables(mysqli$db,string$table):int{$s=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->bind_param('s',$table);$s->execute();$count=(int)$s->get_result()->fetch_row()[0];$s->close();return$count;}
};
