<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface {
 public function up(mysqli $db):void
 {
  $db->query("CREATE TABLE notifications (
   id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, public_id CHAR(26) NOT NULL,
   notification_type VARCHAR(64) NOT NULL, title VARCHAR(200) NOT NULL, summary VARCHAR(500) NOT NULL,
   payload JSON NOT NULL, requires_acknowledgment TINYINT(1) NOT NULL DEFAULT 1,
   priority VARCHAR(20) NOT NULL DEFAULT 'normal', source_entity_type VARCHAR(50) NOT NULL,
   source_entity_public_id CHAR(26) NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
   published_at DATETIME NOT NULL, expires_at DATETIME NULL, status VARCHAR(20) NOT NULL DEFAULT 'published',
   created_at DATETIME NOT NULL, updated_at DATETIME NULL,
   PRIMARY KEY (id), UNIQUE KEY uq_notifications_public_id (public_id),
   UNIQUE KEY uq_notifications_source (notification_type,source_entity_public_id),
   KEY idx_notifications_status_published (status,published_at),
   KEY idx_notifications_type_published (notification_type,published_at),
   KEY idx_notifications_created_by (created_by_user_id),
   CONSTRAINT fk_notifications_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT chk_notifications_type CHECK (notification_type IN ('employee_created')),
   CONSTRAINT chk_notifications_priority CHECK (priority IN ('normal','important','urgent')),
   CONSTRAINT chk_notifications_status CHECK (status IN ('published','withdrawn')),
   CONSTRAINT chk_notifications_ack CHECK (requires_acknowledgment IN (0,1))
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
  $db->query("CREATE TABLE notification_recipients (
   id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, public_id CHAR(26) NOT NULL,
   notification_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
   business_email_snapshot VARCHAR(254) NULL, assigned_at DATETIME NOT NULL,
   first_viewed_at DATETIME NULL, acknowledged_at DATETIME NULL,
   email_status VARCHAR(30) NOT NULL DEFAULT 'pending', email_sent_at DATETIME NULL,
   email_failed_at DATETIME NULL, email_failure_code VARCHAR(100) NULL,
   created_at DATETIME NOT NULL, updated_at DATETIME NULL,
   PRIMARY KEY (id), UNIQUE KEY uq_notification_recipients_public_id (public_id),
   UNIQUE KEY uq_notification_recipients_notice_user (notification_id,user_id),
   KEY idx_notification_recipients_user_ack (user_id,acknowledged_at,notification_id),
   KEY idx_notification_recipients_notice_ack (notification_id,acknowledged_at),
   KEY idx_notification_recipients_email_status (email_status,notification_id),
   CONSTRAINT fk_notification_recipients_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT fk_notification_recipients_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT chk_notification_recipients_email_status CHECK (email_status IN ('pending','sent','failed','skipped_no_email'))
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
 }
 public function down(mysqli $db):void
 {
  foreach(['notification_recipients','notifications'] as $table){$result=$db->query("SELECT COUNT(*) FROM {$table}");$count=(int)$result->fetch_row()[0];$result->free();if($count>0)throw new MigrationException('Cannot roll back Notifications while historical notice or recipient records exist.');}
  $db->query('DROP TABLE notification_recipients');$db->query('DROP TABLE notifications');
 }
};
