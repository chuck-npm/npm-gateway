<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;

use NpmGateway\Notifications\RmAuditEmailSender;
use NpmGateway\Services\RmAuditRichTextSanitizer;
use PHPUnit\Framework\TestCase;

final class RmAuditDetailEmailRepairTest extends TestCase
{
    public function testBothDetailViewsHaveBalancedAlertControlFlowAndExpectedActions(): void
    {
        foreach (['corporate', 'community-actions'] as $area) {
            $path = dirname(__DIR__, 2).'/resources/views/'.$area.'/rm-audits/show.php';
            $source = (string) file_get_contents($path);
            self::assertSame(1, substr_count($source, 'foreach(['));
            self::assertSame(1, substr_count($source, 'endforeach;'));
            self::assertStringContainsString('if($$value!==\'\'):', $source);
            self::assertStringContainsString('endif;', $source);
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path), $output, $code);
            self::assertSame(0, $code, implode("\n", $output));
        }
        $corporate = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/corporate/rm-audits/show.php');
        self::assertStringContainsString("if(\$audit['status']==='completed')", $corporate);
        self::assertStringContainsString('Back to RM Audits', $corporate);
        self::assertStringContainsString('Return Audit', $corporate);
    }

    public function testRmAuditEmailsRenderOnlySanitizedFindingsAsTrustedHtml(): void
    {
        $clean = (new RmAuditRichTextSanitizer())->sanitize('<p><strong>Lease issue</strong></p><ul><li>Missing initials</li></ul><a href="https://example.test" onclick="bad()">Safe link</a><a href="javascript:alert(1)">Unsafe link</a><script>alert(1)</script><img src=x>');
        $audit = [
            'public_id' => str_repeat('A', 26), 'property_name' => 'Alpha <script>', 'property_slug' => 'alpha',
            'manager_email' => 'manager@example.test', 'tenant_name' => 'Tenant <b>Name</b>', 'unit_identifier' => '1 & 2',
            'submitted_by_name' => 'Manager', 'submitted_at' => '2026-08-07 09:00:00',
            'audit_findings_html' => $clean['html'], 'audit_findings_text' => $clean['text'],
        ];
        $sent = [];
        $sender = new RmAuditEmailSender(['app_url' => 'https://gateway.example.test', 'reviewer_email' => 'reviewer@example.test', 'corporate_test_mode' => true, 'corporate_test_email' => 'test@example.test'], function (...$args) use (&$sent): bool { $sent[] = $args; return true; });
        self::assertTrue($sender->submitted($audit));
        self::assertTrue($sender->returned($audit, 'Fix <b>this</b> & confirm.'));
        self::assertTrue($sender->completed($audit));
        self::assertCount(3, $sent);
        self::assertSame('manager@example.test', $sent[0][0]);
        self::assertSame('manager@example.test', $sent[1][0]);
        self::assertSame('test@example.test', $sent[2][0]);
        foreach ($sent as [, , $html, $text]) {
            self::assertStringContainsString('NPM Gateway', $html);
            self::assertStringContainsString('<strong>Lease issue</strong>', $html);
            self::assertStringContainsString('<ul><li>Missing initials</li></ul>', $html);
            self::assertStringNotContainsString('&lt;strong&gt;Lease issue', $html);
            self::assertStringNotContainsString('<script', $html);
            self::assertStringNotContainsString('<img', $html);
            self::assertStringNotContainsString('onclick=', $html);
            self::assertStringNotContainsString('javascript:', $html);
            self::assertStringContainsString('Alpha &lt;script&gt;', $html);
            self::assertStringContainsString('Tenant &lt;b&gt;Name&lt;/b&gt;', $html);
            self::assertStringContainsString('Lease issue', $text);
            self::assertStringContainsString('Missing initials', $text);
            self::assertStringNotContainsString('<strong>', $text);
            self::assertStringNotContainsString('<ul>', $text);
        }
        self::assertStringContainsString('Fix &lt;b&gt;this&lt;/b&gt; &amp; confirm.', $sent[1][2]);
        self::assertStringContainsString('Fix <b>this</b> & confirm.', $sent[1][3]);
    }
}
