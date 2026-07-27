<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

final class AuthenticationSecuritySchema
{
    public const MIGRATION = '202607270002_authentication_security';

    /**
     * @return array<string, array{
     *   columns: list<string>,
     *   forbidden_columns: list<string>,
     *   indexes: list<string>,
     *   foreign_keys: list<string>,
     *   checks: list<string>
     * }>
     */
    public static function expectations(): array
    {
        return [
            'user_sessions' => [
                'columns' => ['id', 'public_id', 'user_id', 'session_token_hash', 'ip_hash', 'user_agent', 'last_activity_at', 'idle_expires_at', 'absolute_expires_at', 'rotated_at', 'revoked_at', 'revoked_by', 'revocation_reason', 'created_at'],
                'forbidden_columns' => ['session_token', 'updated_at', 'created_by', 'updated_by'],
                'indexes' => ['PRIMARY', 'uq_user_sessions_public_id', 'uq_user_sessions_token_hash', 'idx_user_sessions_user_active', 'idx_user_sessions_idle_expiry', 'idx_user_sessions_absolute_expiry', 'idx_user_sessions_revoked_at'],
                'foreign_keys' => ['fk_user_sessions_user', 'fk_user_sessions_revoked_by'],
                'checks' => ['chk_user_sessions_token_hash', 'chk_user_sessions_ip_hash', 'chk_user_sessions_expiration_order', 'chk_user_sessions_rotation_order', 'chk_user_sessions_revocation_reason', 'chk_user_sessions_revocation_state'],
            ],
            'login_attempts' => [
                'columns' => ['id', 'public_id', 'submitted_username_hash', 'user_id', 'was_successful', 'failure_reason', 'ip_hash', 'user_agent', 'attempted_at'],
                'forbidden_columns' => ['submitted_username', 'updated_at', 'created_by', 'updated_by'],
                'indexes' => ['PRIMARY', 'uq_login_attempts_public_id', 'idx_login_attempts_username_time', 'idx_login_attempts_user_time', 'idx_login_attempts_ip_time', 'idx_login_attempts_result_time', 'idx_login_attempts_attempted_at'],
                'foreign_keys' => ['fk_login_attempts_user'],
                'checks' => ['chk_login_attempts_username_hash', 'chk_login_attempts_ip_hash', 'chk_login_attempts_success_value', 'chk_login_attempts_failure_reason', 'chk_login_attempts_result_consistency'],
            ],
        ];
    }
}
