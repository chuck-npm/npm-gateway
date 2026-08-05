<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\EmployeeEmergencyContactSchema as Schema;
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface
{
 public function up(mysqli $db):void
 {
  if($this->count($db,"SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='employees'")!==1)throw new MigrationException('Expected employees table is missing.');
  if($this->count($db,"SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='employees' AND COLUMN_NAME='id' AND COLUMN_TYPE='bigint unsigned' AND COLUMN_KEY='PRI'")!==1)throw new MigrationException('Expected employees primary key is missing.');
  if($this->count($db,"SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".Schema::TABLE."'")!==0)throw new MigrationException('employee_emergency_contacts already exists.');
  if($this->count($db,"SELECT COUNT(*) FROM information_schema.COLLATIONS WHERE COLLATION_NAME='utf8mb4_0900_ai_ci' AND CHARACTER_SET_NAME='utf8mb4'")!==1)throw new MigrationException('Required character set and collation are unavailable.');
  $db->query("CREATE TABLE employee_emergency_contacts (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,public_id CHAR(26) NOT NULL,employee_id BIGINT UNSIGNED NOT NULL,first_name VARCHAR(100) NOT NULL,last_name VARCHAR(100) NOT NULL,relationship VARCHAR(100) NOT NULL,primary_phone VARCHAR(30) NOT NULL,alternate_phone VARCHAR(30) NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY (id),UNIQUE KEY uq_employee_emergency_contacts_public_id (public_id),UNIQUE KEY uq_employee_emergency_contacts_employee (employee_id),CONSTRAINT fk_employee_emergency_contacts_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
 }
 public function down(mysqli $db):void
 {if($this->count($db,"SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='employee_emergency_contacts'")!==1)throw new MigrationException('Expected employee_emergency_contacts table is missing.');if($this->count($db,'SELECT COUNT(*) FROM employee_emergency_contacts')!==0)throw new MigrationException('Cannot roll back Emergency Contact Information while contact rows exist.');$db->query('DROP TABLE employee_emergency_contacts');}
 private function count(mysqli $db,string $sql):int{return (int)$db->query($sql)->fetch_row()[0];}
};
