<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;

use NpmGateway\Notifications\RmAuditEmailSender;
use PHPUnit\Framework\TestCase;

final class RmAuditConfigurationTest extends TestCase
{
    public function testConfigReadsImmutableDotenvSuperglobalsBeforeGetenv(): void
    {
        $keys = ['RM_AUDIT_REVIEWER_EMAIL', 'RM_AUDIT_CORPORATE_TEST_MODE', 'RM_AUDIT_CORPORATE_TEST_EMAIL', 'APP_URL'];
        $oldEnv = $oldServer = [];
        foreach ($keys as $key) { $oldEnv[$key] = $_ENV[$key] ?? null; $oldServer[$key] = $_SERVER[$key] ?? null; }
        try {
            putenv('RM_AUDIT_CORPORATE_TEST_MODE=false');
            $_ENV['RM_AUDIT_REVIEWER_EMAIL'] = 'reviewer@example.test';
            $_ENV['RM_AUDIT_CORPORATE_TEST_MODE'] = 'true';
            $_ENV['RM_AUDIT_CORPORATE_TEST_EMAIL'] = 'noc@example.test';
            $_ENV['APP_URL'] = 'https://gateway.example.test/';
            $config = require dirname(__DIR__, 2).'/config/rm-audits.php';
            self::assertTrue($config['corporate_test_mode']);
            self::assertSame('noc@example.test', $config['corporate_test_email']);
            self::assertSame('reviewer@example.test', $config['reviewer_email']);
            self::assertSame('https://gateway.example.test', $config['app_url']);
        } finally {
            foreach ($keys as $key) {
                if ($oldEnv[$key] === null) unset($_ENV[$key]); else $_ENV[$key] = $oldEnv[$key];
                if ($oldServer[$key] === null) unset($_SERVER[$key]); else $_SERVER[$key] = $oldServer[$key];
            }
            putenv('RM_AUDIT_CORPORATE_TEST_MODE');
        }
    }

    public function testCorporateRecipientModesFailClosedAndManagerRoutingIsIndependent(): void
    {
        $audit = $this->audit();
        $recipients = [];
        $test = new RmAuditEmailSender($this->config(true, 'test@example.test', 'production@example.test'), function (string $to) use (&$recipients): bool { $recipients[] = $to; return true; });
        self::assertTrue($test->completed($audit));
        self::assertTrue($test->submitted($audit));
        self::assertTrue($test->returned($audit, 'Please correct this item.'));
        self::assertSame(['test@example.test', 'manager@example.test', 'manager@example.test'], $recipients);
        self::assertNotContains('production@example.test', $recipients);

        foreach (['', 'invalid-address'] as $invalid) {
            $called = false;
            $sender = new RmAuditEmailSender($this->config(true, $invalid, 'production@example.test'), function () use (&$called): bool { $called = true; return true; });
            self::assertFalse($sender->completed($audit));
            self::assertFalse($called);
        }
        foreach (['', 'invalid-address'] as $invalid) {
            $called = false;
            $sender = new RmAuditEmailSender($this->config(false, 'test@example.test', $invalid), function () use (&$called): bool { $called = true; return true; });
            self::assertFalse($sender->completed($audit));
            self::assertFalse($called);
        }
        $production = [];
        $sender = new RmAuditEmailSender($this->config(false, 'test@example.test', 'production@example.test'), function (string $to) use (&$production): bool { $production[] = $to; return true; });
        self::assertTrue($sender->completed($audit));
        self::assertSame(['production@example.test'], $production);
    }

    public function testCompletionCommitsBeforeNotificationAndDoesNotRollBackDeliveryFailure(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Services/RmAuditService.php');
        $commit = strpos($source, '$this->tx->commit();', strpos($source, 'function complete'));
        $delivery = strpos($source, '$this->mail->completed($done)', strpos($source, 'function complete'));
        self::assertIsInt($commit);
        self::assertIsInt($delivery);
        self::assertLessThan($delivery, $commit);
    }

    private function config(bool $testMode, string $testEmail, string $reviewer): array
    {
        return ['corporate_test_mode' => $testMode, 'corporate_test_email' => $testEmail, 'reviewer_email' => $reviewer, 'app_url' => 'https://gateway.example.test'];
    }

    private function audit(): array
    {
        return ['public_id' => str_repeat('A', 26), 'property_name' => 'Pine Hill', 'property_slug' => 'pine-hill', 'manager_email' => 'manager@example.test', 'tenant_name' => 'Tenant', 'unit_identifier' => '19A', 'submitted_by_name' => 'Manager', 'submitted_at' => '2026-08-07 09:00:00', 'audit_findings_html' => '<p>Finding</p>', 'audit_findings_text' => 'Finding'];
    }
}
