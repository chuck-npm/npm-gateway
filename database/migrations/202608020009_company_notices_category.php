<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\CompanyNoticesCategorySchema as Schema;
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface {
 public function up(mysqli $db):void
 {
  $this->assertConstraint($db,'user_category_access','chk_user_category_access_category',Schema::BEFORE_CATEGORIES);$this->assertConstraint($db,'notifications','chk_notifications_type',Schema::BEFORE_TYPES);
  if($this->count($db,"SELECT COUNT(*) FROM user_category_access WHERE category NOT IN ('finance','human-resources','marketing','admin','credit-cards')")>0)throw new MigrationException('Unsupported category rows prevent Company Notices migration.');
  if($this->count($db,"SELECT COUNT(*) FROM notifications WHERE notification_type NOT IN ('employee_created')")>0)throw new MigrationException('Unsupported notification type rows prevent Company Notices migration.');
  if($this->count($db,"SELECT COUNT(*) FROM user_category_access WHERE category='company-notices'")>0||$this->count($db,"SELECT COUNT(*) FROM notifications WHERE notification_type='company_notice'")>0)throw new MigrationException('Unexpected Company Notices data exists before migration.');
  $db->query('ALTER TABLE user_category_access DROP CHECK chk_user_category_access_category, ADD CONSTRAINT chk_user_category_access_category CHECK (category IN (\'finance\',\'human-resources\',\'company-notices\',\'marketing\',\'admin\',\'credit-cards\'))');
  try{$db->query('ALTER TABLE notifications DROP CHECK chk_notifications_type, ADD CONSTRAINT chk_notifications_type CHECK (notification_type IN (\'employee_created\',\'company_notice\'))');}catch(Throwable $e){$db->query('ALTER TABLE user_category_access DROP CHECK chk_user_category_access_category, ADD CONSTRAINT chk_user_category_access_category CHECK (category IN (\'finance\',\'human-resources\',\'marketing\',\'admin\',\'credit-cards\'))');throw new MigrationException('Notification type constraint update failed; category constraint was restored.',0,$e);}
 }
 public function down(mysqli $db):void
 {
  $this->assertConstraint($db,'user_category_access','chk_user_category_access_category',Schema::CATEGORIES);$this->assertConstraint($db,'notifications','chk_notifications_type',Schema::NOTIFICATION_TYPES);
  if($this->count($db,"SELECT COUNT(*) FROM user_category_access WHERE category='company-notices'")>0)throw new MigrationException('Cannot roll back Company Notices while active authorization memberships exist.');
  if($this->count($db,"SELECT COUNT(*) FROM notifications WHERE notification_type='company_notice'")>0)throw new MigrationException('Cannot roll back Company Notices while historical notices exist.');
  $db->query('ALTER TABLE notifications DROP CHECK chk_notifications_type, ADD CONSTRAINT chk_notifications_type CHECK (notification_type IN (\'employee_created\'))');
  try{$db->query('ALTER TABLE user_category_access DROP CHECK chk_user_category_access_category, ADD CONSTRAINT chk_user_category_access_category CHECK (category IN (\'finance\',\'human-resources\',\'marketing\',\'admin\',\'credit-cards\'))');}catch(Throwable $e){$db->query('ALTER TABLE notifications DROP CHECK chk_notifications_type, ADD CONSTRAINT chk_notifications_type CHECK (notification_type IN (\'employee_created\',\'company_notice\'))');throw new MigrationException('Category constraint rollback failed; notification constraint was restored.',0,$e);}
 }
 private function assertConstraint(mysqli $db,string $table,string $name,array $expected):void{$s=$db->prepare("SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME=? AND tc.CONSTRAINT_NAME=? AND tc.CONSTRAINT_TYPE='CHECK'");$s->bind_param('ss',$table,$name);$s->execute();$clause=$s->get_result()->fetch_row()[0]??null;$s->close();if(!is_string($clause))throw new MigrationException("Expected constraint {$name} is missing.");preg_match_all("/'([^']+)'/",str_replace("\\'","'",$clause),$matches);if(array_values(array_unique($matches[1]??[]))!==$expected)throw new MigrationException("Constraint {$name} differs from the expected schema.");}
 private function count(mysqli $db,string $sql):int{return (int)$db->query($sql)->fetch_row()[0];}
};
