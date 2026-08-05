<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\ApplicationReviewsCategorySchema as Schema;
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface {
 public function up(mysqli $db):void
 {
  $this->assertTable($db);$this->assertConstraint($db,Schema::BEFORE_CATEGORIES);
  if($this->count($db,"SELECT COUNT(*) FROM user_category_access WHERE category NOT IN ('operations','finance','human-resources','company-notices','marketing','admin','credit-cards')")>0)throw new MigrationException('Unsupported category rows prevent Application Reviews category migration.');
  $db->query("ALTER TABLE user_category_access DROP CHECK chk_user_category_access_category, ADD CONSTRAINT chk_user_category_access_category CHECK (category IN ('operations','finance','human-resources','company-notices','application-reviews','marketing','admin','credit-cards'))");
 }
 public function down(mysqli $db):void
 {
  $this->assertTable($db);$this->assertConstraint($db,Schema::SQL_CATEGORIES);
  if($this->count($db,"SELECT COUNT(*) FROM user_category_access WHERE category='application-reviews'")>0)throw new MigrationException('Cannot roll back Application Reviews while active application-reviews memberships exist.');
  $db->query("ALTER TABLE user_category_access DROP CHECK chk_user_category_access_category, ADD CONSTRAINT chk_user_category_access_category CHECK (category IN ('operations','finance','human-resources','company-notices','marketing','admin','credit-cards'))");
 }
 private function assertTable(mysqli $db):void{if($this->count($db,"SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_category_access'")!==1)throw new MigrationException('Expected user_category_access table is missing.');}
 private function assertConstraint(mysqli $db,array $expected):void{$s=$db->prepare("SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='user_category_access' AND tc.CONSTRAINT_NAME='chk_user_category_access_category' AND tc.CONSTRAINT_TYPE='CHECK'");$s->execute();$clause=$s->get_result()->fetch_row()[0]??null;$s->close();if(!is_string($clause))throw new MigrationException('Expected Application Reviews category constraint is missing.');preg_match_all("/'([^']+)'/",str_replace("\\'","'",$clause),$matches);if(array_values(array_unique($matches[1]??[]))!==$expected)throw new MigrationException('Application Reviews category constraint differs from the expected schema.');}
 private function count(mysqli $db,string $sql):int{return (int)$db->query($sql)->fetch_row()[0];}
};
