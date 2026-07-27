<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationInterface;
use PHPUnit\Framework\TestCase;

final class AuthenticationSecurityMigrationTest extends TestCase
{
    private string $sql;
    private string $up;
    private string $down;
    private string $path;

    protected function setUp(): void
    {
        $this->path = dirname(__DIR__, 2) . '/database/migrations/202607270002_authentication_security.php';
        $this->sql = (string) file_get_contents($this->path);
        [$this->up, $this->down] = explode('public function down', $this->sql, 2);
    }

    public function testFilenameContractAndMigrationOrder(): void
    {
        self::assertTrue(MigrationDiscovery::isValidFilename(basename($this->path)));
        self::assertInstanceOf(MigrationInterface::class, require $this->path);
        self::assertLessThan(strpos($this->up, 'CREATE TABLE user_sessions'), strpos($this->up, 'DROP COLUMN must_change_password'));
        self::assertLessThan(strpos($this->up, 'CREATE TABLE login_attempts'), strpos($this->up, 'CREATE TABLE user_sessions'));
        self::assertLessThan(strpos($this->down, 'DROP TABLE IF EXISTS user_sessions'), strpos($this->down, 'DROP TABLE IF EXISTS login_attempts'));
        self::assertLessThan(strpos($this->down, 'ADD COLUMN must_change_password'), strpos($this->down, 'DROP TABLE IF EXISTS user_sessions'));
        self::assertStringContainsString('must_change_password TINYINT(1) NOT NULL DEFAULT 0', $this->down);
    }

    public function testGeneralSqlStandardsAndProhibitions(): void
    {
        self::assertSame(2, substr_count($this->sql, 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'));
        foreach ([' ENUM(', 'ON DELETE CASCADE', 'CREATE TRIGGER', 'CREATE PROCEDURE', 'FOREIGN_KEY_CHECKS', 'INSERT INTO'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, strtoupper($this->sql));
        }
        foreach (['password_reset_tokens', 'password_history', 'remembered_devices', 'authentication_events', 'recovery_codes', 'roles', 'permissions'] as $table) {
            self::assertStringNotContainsString("CREATE TABLE {$table}", $this->sql);
        }
    }

    public function testSessionSchema(): void
    {
        $session = $this->tableSql('user_sessions', 'login_attempts');
        foreach ([
            'session_token_hash CHAR(64)', 'uq_user_sessions_token_hash', 'fk_user_sessions_user',
            'fk_user_sessions_revoked_by', 'idle_expires_at DATETIME', 'absolute_expires_at DATETIME',
            'chk_user_sessions_expiration_order', 'chk_user_sessions_revocation_state',
            "'logout'", "'password_reset'", "'account_disabled'", "'administrator_revoked'",
            "'idle_expired'", "'absolute_expired'", "'security_rotation'", "'superseded'",
        ] as $required) {
            self::assertStringContainsString($required, $session);
        }
        self::assertDoesNotMatchRegularExpression('/\bsession_token\s+(?:CHAR|VARCHAR|TEXT)/i', $session);
        foreach (['updated_at', 'created_by'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $session);
        }
        self::assertDoesNotMatchRegularExpression('/\bupdated_by\s+BIGINT/i', $session);
    }

    public function testLoginAttemptSchema(): void
    {
        $attempt = $this->tableSql('login_attempts', null);
        foreach ([
            'submitted_username_hash CHAR(64)', 'idx_login_attempts_username_time',
            'idx_login_attempts_user_time', 'idx_login_attempts_ip_time',
            'chk_login_attempts_result_consistency', "'invalid_credentials'",
            "'account_disabled'", "'account_locked'", "'rate_limited'",
        ] as $required) {
            self::assertStringContainsString($required, $attempt);
        }
        self::assertDoesNotMatchRegularExpression('/\bsubmitted_username\s+(?:CHAR|VARCHAR|TEXT)/i', $attempt);
        foreach (['updated_at', 'created_by', 'updated_by'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $attempt);
        }
    }

    public function testRequiredUserSecurityColumnsRemainReferencedByFoundation(): void
    {
        $foundation = (string) file_get_contents(dirname(__DIR__, 2) . '/database/migrations/202607270001_foundation.php');
        foreach (['password_changed_at', 'password_reset_at', 'failed_login_count', 'locked_until'] as $column) {
            self::assertStringContainsString($column, $foundation);
        }
        self::assertStringContainsString('DROP COLUMN must_change_password', $this->up);
    }

    public function testAuthenticationPolicyIsDocumented(): void
    {
        $dictionary = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/data-dictionary.md');
        foreach ([
            'Administrator password replacement',
            '60-minute idle',
            'eight-hour',
            'Five consecutive failures',
            '15 minutes',
            'Remember me',
            'no self-service recovery',
            'Email archives containing current credentials are highly sensitive',
            'at least 24 characters',
            'Plaintext passwords are forbidden',
        ] as $policy) {
            self::assertStringContainsStringIgnoringCase($policy, $dictionary);
        }
    }

    private function tableSql(string $table, ?string $nextTable): string
    {
        $start = (int) strpos($this->up, "CREATE TABLE {$table}");
        $end = $nextTable === null ? strlen($this->up) : (int) strpos($this->up, "CREATE TABLE {$nextTable}");

        return substr($this->up, $start, $end - $start);
    }
}
