<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationException;
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface {
 public function up(mysqli $db):void
 {
  $db->query("CREATE TABLE storage_objects (
   id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, public_id CHAR(26) NOT NULL,
   provider VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
   provider_container VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
   object_key VARCHAR(1024) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
   original_filename VARCHAR(255) NOT NULL, display_filename VARCHAR(255) NOT NULL,
   mime_type VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
   byte_size BIGINT UNSIGNED NOT NULL, sha256_hex CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
   lifecycle_state VARCHAR(20) NOT NULL DEFAULT 'temporary', uploaded_by_user_id BIGINT UNSIGNED NOT NULL,
   temporary_review_owner_user_id BIGINT UNSIGNED NULL, published_at DATETIME NULL,
   deleted_at DATETIME NULL, deleted_by_user_id BIGINT UNSIGNED NULL,
   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (id), UNIQUE KEY uq_storage_objects_public_id (public_id),
   UNIQUE KEY uq_storage_objects_provider_key (provider,provider_container,object_key),
   KEY idx_storage_objects_lifecycle_created (lifecycle_state,created_at),
   KEY idx_storage_objects_temporary_owner (temporary_review_owner_user_id,lifecycle_state,created_at),
   KEY idx_storage_objects_uploader (uploaded_by_user_id,created_at), KEY idx_storage_objects_sha256 (sha256_hex),
   KEY idx_storage_objects_deleted_by (deleted_by_user_id),
   CONSTRAINT fk_storage_objects_uploaded_by FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT fk_storage_objects_temporary_owner FOREIGN KEY (temporary_review_owner_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT fk_storage_objects_deleted_by FOREIGN KEY (deleted_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT chk_storage_objects_provider CHECK (provider REGEXP '^[a-z][a-z0-9_-]{0,31}$'),
   CONSTRAINT chk_storage_objects_provider_container CHECK (CHAR_LENGTH(provider_container) BETWEEN 1 AND 255),
   CONSTRAINT chk_storage_objects_object_key CHECK (CHAR_LENGTH(object_key) BETWEEN 1 AND 1024),
   CONSTRAINT chk_storage_objects_filenames CHECK (CHAR_LENGTH(original_filename) BETWEEN 1 AND 255 AND CHAR_LENGTH(display_filename) BETWEEN 1 AND 255),
   CONSTRAINT chk_storage_objects_mime_type CHECK (CHAR_LENGTH(mime_type) BETWEEN 1 AND 255),
   CONSTRAINT chk_storage_objects_byte_size CHECK (byte_size > 0),
   CONSTRAINT chk_storage_objects_sha256 CHECK (sha256_hex REGEXP '^[0-9a-f]{64}$'),
   CONSTRAINT chk_storage_objects_lifecycle CHECK (lifecycle_state IN ('temporary','published','deleted')),
   CONSTRAINT chk_storage_objects_lifecycle_metadata CHECK ((lifecycle_state='temporary' AND temporary_review_owner_user_id IS NOT NULL AND published_at IS NULL AND deleted_at IS NULL AND deleted_by_user_id IS NULL) OR (lifecycle_state='published' AND temporary_review_owner_user_id IS NULL AND published_at IS NOT NULL AND deleted_at IS NULL AND deleted_by_user_id IS NULL) OR (lifecycle_state='deleted' AND temporary_review_owner_user_id IS NULL AND deleted_at IS NOT NULL AND deleted_by_user_id IS NOT NULL))
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
  try{$db->query("CREATE TABLE notification_storage_objects (
   id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, public_id CHAR(26) NOT NULL,
   notification_id BIGINT UNSIGNED NOT NULL, storage_object_id BIGINT UNSIGNED NOT NULL,
   asset_role VARCHAR(30) NOT NULL, sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
   linked_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (id), UNIQUE KEY uq_notification_storage_objects_public_id (public_id),
   UNIQUE KEY uq_notification_storage_objects_notice_object (notification_id,storage_object_id),
   KEY idx_notification_storage_objects_notice_role (notification_id,asset_role,sort_order),
   KEY idx_notification_storage_objects_linked_by (linked_by_user_id),
   CONSTRAINT fk_notification_storage_objects_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT fk_notification_storage_objects_object FOREIGN KEY (storage_object_id) REFERENCES storage_objects(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT fk_notification_storage_objects_linked_by FOREIGN KEY (linked_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT chk_notification_storage_objects_role CHECK (asset_role IN ('attachment','embedded_image'))
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");}catch(Throwable $e){$db->query('DROP TABLE storage_objects');throw new MigrationException('Notification storage relationship creation failed; the storage metadata table was removed.',0,$e);}
 }
 public function down(mysqli $db):void
 {
  foreach(['notification_storage_objects','storage_objects'] as $table){$result=$db->query("SELECT COUNT(*) FROM {$table}");$count=(int)$result->fetch_row()[0];$result->free();if($count>0)throw new MigrationException('Cannot roll back Gateway Storage while stored-object or notification-asset records exist.');}
  $db->query('DROP TABLE notification_storage_objects');$db->query('DROP TABLE storage_objects');
 }
};
