<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;

use NpmGateway\Notifications\ApplicationReviewEmailSender;
use NpmGateway\Notifications\GatewayEmailRenderer;
use NpmGateway\Notifications\RmAuditEmailSender;
use NpmGateway\Notifications\RmCorrectionEmailSender;
use NpmGateway\Services\RmAuditRichTextSanitizer;
use NpmGateway\ValueObjects\GatewayEmailMessage;
use PHPUnit\Framework\TestCase;

final class RmAuditFinalPresentationTest extends TestCase
{
    public function testSharedWorkflowFooterIsLeftAlignedAcrossEmailTypes(): void
    {
        $shared = (new GatewayEmailRenderer())->render(new GatewayEmailMessage('Preview', 'WORKFLOW', 'Shared'))['html'];
        self::assertStringContainsString('text-align:left;', $shared);
        self::assertStringNotContainsString('text-align:center;', $shared);
        foreach (['NPM Gateway', 'Internal Workflow Notification', 'This message was generated automatically by NPM Gateway.', 'Please do not reply to this email.', 'NPM Properties Inc.'] as $wording) self::assertStringContainsString($wording, $shared);

        $auditMail = [];
        (new RmAuditEmailSender($this->auditConfig(), function (...$args) use (&$auditMail): bool { $auditMail = $args; return true; }))->completed($this->audit());
        $correctionMail = [];
        (new RmCorrectionEmailSender(['test_mode'=>true,'test_email'=>'test@example.test','reviewer_email'=>'reviewer@example.test','app_url'=>'https://gateway.example.test'], function (...$args) use (&$correctionMail): bool { $correctionMail = $args; return true; }))->submission($this->correction());
        $application = (new ApplicationReviewEmailSender(['app_url'=>'https://gateway.example.test']))->submissionContent($this->review());
        foreach ([$auditMail[2], $correctionMail[2], $application['html']] as $html) {
            self::assertStringContainsString('text-align:left;', $html);
            self::assertStringNotContainsString('text-align:center;', $html);
        }
    }

    public function testSharedRmAuditDetailRendersEstablishedTimelineInChronologicalOrder(): void
    {
        $audit = $this->audit();
        $audit['status'] = 'completed'; $audit['updated_at'] = '2026-08-07 18:43:00'; $audit['completed_at'] = '2026-08-07 18:43:00'; $audit['completed_by_name'] = 'Amanda Watson';
        $audit['history'] = [
            ['event_type'=>'submitted','actor_name'=>'Chuck Lundquist','created_at'=>'2026-08-07 18:32:00','comments'=>null],
            ['event_type'=>'completed','actor_name'=>'Amanda Watson','created_at'=>'2026-08-07 18:33:00','comments'=>null],
            ['event_type'=>'returned','actor_name'=>'Chuck Lundquist','created_at'=>'2026-08-07 18:39:00','comments'=>'The signed lease is still missing.'],
            ['event_type'=>'completed','actor_name'=>'Amanda Watson','created_at'=>'2026-08-07 18:43:00','comments'=>null],
        ];
        ob_start(); require dirname(__DIR__, 2).'/resources/views/components/rm-audit-detail.php'; $html = (string) ob_get_clean();
        foreach (['gateway-review-timeline','gateway-review-timeline__events','gateway-review-timeline__event','gateway-review-timeline__label','gateway-review-timeline__meta','gateway-review-timeline__comments','Current Status','Audit Findings','The signed lease is still missing.'] as $required) self::assertStringContainsString($required, $html);
        self::assertSame(4, substr_count($html, 'gateway-review-timeline__event"'));
        self::assertSame(2, substr_count($html, '>Completed</strong>'));
        self::assertStringNotContainsString('gateway-timeline', $html);
        $timeline = substr($html, (int) strpos($html, '<section class="gateway-review-timeline"'));
        self::assertLessThan(strpos($timeline, 'August 7, 2026 at 6:33 PM'), strpos($timeline, 'August 7, 2026 at 6:32 PM'));
        self::assertLessThan(strpos($timeline, 'August 7, 2026 at 6:39 PM'), strpos($timeline, 'August 7, 2026 at 6:33 PM'));
        self::assertLessThan(strpos($timeline, 'August 7, 2026 at 6:43 PM'), strpos($timeline, 'August 7, 2026 at 6:39 PM'));
        foreach (['corporate','community-actions'] as $area) self::assertStringContainsString("require\$components.'/rm-audit-detail.php'", (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/'.$area.'/rm-audits/show.php'));
    }

    public function testUnicodeSurvivesSanitizerHtmlAndPlainTextRenderingWithoutReplacement(): void
    {
        $unicode = 'Lease – signed copy missing; Resident’s application; $1,250.00; Café; José; “quoted text”; • bullet item';
        $clean = (new RmAuditRichTextSanitizer())->sanitize('<p><strong>'.$unicode.'</strong></p>');
        $audit = $this->audit(); $audit['audit_findings_html'] = $clean['html']; $audit['audit_findings_text'] = $clean['text'];
        $sent = [];
        self::assertTrue((new RmAuditEmailSender($this->auditConfig(), function (...$args) use (&$sent): bool { $sent = $args; return true; }))->completed($audit));
        foreach ([$clean['html'], $clean['text'], $sent[2], $sent[3]] as $value) {
            self::assertTrue(mb_check_encoding($value, 'UTF-8'));
            self::assertStringContainsString($unicode, $value);
            self::assertStringNotContainsString("\u{FFFD}", $value);
            self::assertStringNotContainsString("\xC3\xAF\xC2\xBF\xC2\xBD", $value);
        }
        self::assertStringNotContainsString('<strong>', $sent[3]);
        $sanitizer = (string) file_get_contents(dirname(__DIR__, 2).'/app/Services/RmAuditRichTextSanitizer.php');
        self::assertStringContainsString("private const TAGS=['p','br','strong','b','em','i','u','ol','ul','li','a'];", $sanitizer);
    }

    private function auditConfig(): array { return ['corporate_test_mode'=>true,'corporate_test_email'=>'test@example.test','reviewer_email'=>'reviewer@example.test','app_url'=>'https://gateway.example.test']; }
    private function audit(): array { return ['public_id'=>str_repeat('A',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','manager_email'=>'manager@example.test','tenant_name'=>'Tenant','unit_identifier'=>'19A','submitted_by_name'=>'Chuck Lundquist','submitted_at'=>'2026-08-07 18:32:00','audit_findings_html'=>'<p>Finding</p>','audit_findings_text'=>'Finding']; }
    private function correction(): array { return ['public_id'=>str_repeat('C',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','lot_address'=>'19A','tenant_name'=>'Tenant','submitted_by_name'=>'Manager','submitted_at'=>'2026-08-07 18:32:00','correction_request'=>'Correction','manager_email'=>'manager@example.test']; }
    private function review(): array { return ['public_id'=>str_repeat('R',26),'property_name'=>'Pine Hill','property_slug'=>'pine-hill','prospect_name'=>'Prospect','submitted_by_name'=>'Manager','submitted_at'=>'2026-08-07 18:32:00','manager_comments'=>'Comments','status'=>'pending_review','reviewed_by_name'=>'','reviewed_at'=>null,'reviewer_comments'=>'']; }
}
