<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(mysqli $connection): void
    {
        $connection->query('ALTER TABLE users DROP COLUMN must_change_password');

        $connection->query(<<<'SQL'
CREATE TABLE user_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COMMENT 'Stable ULID-style public identifier.',
    user_id BIGINT UNSIGNED NOT NULL,
    session_token_hash CHAR(64) NOT NULL COMMENT 'Keyed SHA-256 or equivalent hash of the session identifier; raw session identifiers are never stored.',
    ip_hash CHAR(64) NULL COMMENT 'Privacy-preserving hash of the client IP address.',
    user_agent VARCHAR(500) NULL,
    last_activity_at DATETIME NOT NULL,
    idle_expires_at DATETIME NOT NULL COMMENT 'Expiration based on the approved 60-minute idle timeout.',
    absolute_expires_at DATETIME NOT NULL COMMENT 'Hard expiration no later than eight hours after authentication.',
    rotated_at DATETIME NOT NULL COMMENT 'Time the session identifier was last rotated.',
    revoked_at DATETIME NULL,
    revoked_by BIGINT UNSIGNED NULL COMMENT 'Administrator or user account responsible for revocation when applicable.',
    revocation_reason VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_sessions_public_id (public_id),
    UNIQUE KEY uq_user_sessions_token_hash (session_token_hash),
    KEY idx_user_sessions_user_active (user_id, revoked_at, absolute_expires_at),
    KEY idx_user_sessions_idle_expiry (idle_expires_at),
    KEY idx_user_sessions_absolute_expiry (absolute_expires_at),
    KEY idx_user_sessions_revoked_at (revoked_at),
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_user_sessions_revoked_by FOREIGN KEY (revoked_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_user_sessions_token_hash CHECK (session_token_hash REGEXP '^[0-9a-f]{64}$'),
    CONSTRAINT chk_user_sessions_ip_hash CHECK (ip_hash IS NULL OR ip_hash REGEXP '^[0-9a-f]{64}$'),
    CONSTRAINT chk_user_sessions_expiration_order CHECK (idle_expires_at <= absolute_expires_at),
    CONSTRAINT chk_user_sessions_rotation_order CHECK (rotated_at >= created_at),
    CONSTRAINT chk_user_sessions_revocation_reason CHECK (revocation_reason IS NULL OR revocation_reason IN ('logout', 'password_reset', 'account_disabled', 'administrator_revoked', 'idle_expired', 'absolute_expired', 'security_rotation', 'superseded')),
    CONSTRAINT chk_user_sessions_revocation_state CHECK ((revoked_at IS NULL AND revocation_reason IS NULL) OR (revoked_at IS NOT NULL AND revocation_reason IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Raw session identifiers are never persisted; revoked or expired sessions are retained temporarily for security history.'
SQL);

        $connection->query(<<<'SQL'
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL,
    submitted_username_hash CHAR(64) NOT NULL COMMENT 'Privacy-preserving hash of the normalized submitted username.',
    user_id BIGINT UNSIGNED NULL COMMENT 'Matched user when one exists; null for unknown usernames.',
    was_successful TINYINT(1) NOT NULL DEFAULT 0,
    failure_reason VARCHAR(40) NULL,
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_login_attempts_public_id (public_id),
    KEY idx_login_attempts_username_time (submitted_username_hash, attempted_at),
    KEY idx_login_attempts_user_time (user_id, attempted_at),
    KEY idx_login_attempts_ip_time (ip_hash, attempted_at),
    KEY idx_login_attempts_result_time (was_successful, attempted_at),
    KEY idx_login_attempts_attempted_at (attempted_at),
    CONSTRAINT fk_login_attempts_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_login_attempts_username_hash CHECK (submitted_username_hash REGEXP '^[0-9a-f]{64}$'),
    CONSTRAINT chk_login_attempts_ip_hash CHECK (ip_hash IS NULL OR ip_hash REGEXP '^[0-9a-f]{64}$'),
    CONSTRAINT chk_login_attempts_success_value CHECK (was_successful IN (0, 1)),
    CONSTRAINT chk_login_attempts_failure_reason CHECK (failure_reason IS NULL OR failure_reason IN ('invalid_credentials', 'account_disabled', 'account_locked', 'rate_limited')),
    CONSTRAINT chk_login_attempts_result_consistency CHECK ((was_successful = 1 AND failure_reason IS NULL) OR (was_successful = 0 AND failure_reason IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Immutable authentication attempts that deliberately avoid storing guessed usernames in plaintext.'
SQL);
    }

    public function down(mysqli $connection): void
    {
        $connection->query('DROP TABLE IF EXISTS login_attempts');
        $connection->query('DROP TABLE IF EXISTS user_sessions');
        $connection->query(
            'ALTER TABLE users
             ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
             ADD CONSTRAINT chk_users_must_change_password CHECK (must_change_password IN (0, 1))'
        );
    }
};
