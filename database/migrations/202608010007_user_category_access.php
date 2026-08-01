<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface
{
    public function up(mysqli $connection):void
    {
        $connection->query(<<<'SQL'
CREATE TABLE user_category_access (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 public_id CHAR(26) NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 category VARCHAR(50) NOT NULL,
 granted_by_user_id BIGINT UNSIGNED NOT NULL,
 granted_at DATETIME NOT NULL,
 updated_by_user_id BIGINT UNSIGNED NULL,
 updated_at DATETIME NULL,
 PRIMARY KEY (id),
 UNIQUE KEY uq_user_category_access_public_id (public_id),
 UNIQUE KEY uq_user_category_access_user_category (user_id,category),
 KEY idx_user_category_access_category (category),
 KEY idx_user_category_access_granted_by (granted_by_user_id),
 KEY idx_user_category_access_updated_by (updated_by_user_id),
 CONSTRAINT fk_user_category_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
 CONSTRAINT fk_user_category_access_granted_by FOREIGN KEY (granted_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
 CONSTRAINT fk_user_category_access_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
 CONSTRAINT chk_user_category_access_category CHECK (category IN ('finance','human-resources','marketing','admin','credit-cards'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Durable per-user authorization for approved Corporate Gateway categories.'
SQL);
    }
    public function down(mysqli $connection):void
    {
        $count=(int)$connection->query('SELECT COUNT(*) FROM user_category_access')->fetch_row()[0];if($count>0)throw new RuntimeException('Cannot roll back User Category Access while membership rows exist; dropping the table would destroy active authorization records.');$connection->query('DROP TABLE user_category_access');
    }
};
