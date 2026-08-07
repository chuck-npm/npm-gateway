<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;

use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\ValueObjects\GatewayEmailMessage;
use PHPMailer\PHPMailer\PHPMailer;

final class RmAuditEmailSender
{
    private readonly GatewayEmailRenderer $renderer;

    public function __construct(private readonly array $config, private readonly ?\Closure $delivery = null, ?GatewayEmailRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new GatewayEmailRenderer();
    }

    public function submitted(array $audit): bool
    {
        return $this->send('submitted', $this->manager($audit), 'RM Audit Submitted — '.$audit['property_name'], $audit, 'RM Audit Submitted', 'Open', 'pending', [$this->findings($audit)], '/community-actions/'.$audit['property_slug'].'/rm-audits/'.$audit['public_id']);
    }

    public function returned(array $audit, string $comments): bool
    {
        return $this->send('returned', $this->manager($audit), 'RM Audit Returned — '.$audit['property_name'], $audit, 'RM Audit Returned', 'Returned', 'danger', [['title' => 'Return Comments', 'body' => $comments], $this->findings($audit)], '/community-actions/'.$audit['property_slug'].'/rm-audits/'.$audit['public_id']);
    }

    public function completed(array $audit): bool
    {
        $to = ($this->config['corporate_test_mode'] ?? false) === true ? trim((string) ($this->config['corporate_test_email'] ?? '')) : trim((string) ($this->config['reviewer_email'] ?? ''));
        return $this->send('completed', $to, 'RM Audit Completed — '.$audit['property_name'], $audit, 'RM Audit Completed', 'Completed', 'success', [$this->findings($audit)], '/corporate/rm-audits/'.$audit['public_id']);
    }

    private function findings(array $audit): array
    {
        return ['title' => 'Audit Findings', 'trusted_sanitized_html' => (string) $audit['audit_findings_html'], 'plain_text' => (string) $audit['audit_findings_text']];
    }

    private function manager(array $audit): string { return trim((string) ($audit['manager_email'] ?? '')); }

    private function send(string $type, string $to, string $subject, array $audit, string $title, string $status, string $tone, array $sections, string $path): bool
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) return $this->fail($type, 'recipient_unavailable');
        try {
            $rows = [
                ['label' => 'Property', 'value' => $audit['property_name'], 'emphasized' => true],
                ['label' => 'Tenant', 'value' => $audit['tenant_name']],
                ['label' => 'Unit', 'value' => $audit['unit_identifier']],
                ['label' => 'Submitted By', 'value' => $audit['submitted_by_name']],
                ['label' => 'Submitted At', 'value' => $this->date($audit['submitted_at'])],
            ];
            $content = $this->renderer->render(new GatewayEmailMessage('Rent Manager tenant-file audit workflow update.', 'RM AUDIT', $title, $audit['property_name'], $status, $tone, $rows, $sections, 'View RM Audit', rtrim((string) $this->config['app_url'], '/').$path));
            if ($this->delivery) return (bool) ($this->delivery)($to, $subject, $content['html'], $content['text']);
            $smtp = HrEmployeeNotificationConfig::fromArray((array) $this->config['smtp']);
            $mail = new PHPMailer(true); $mail->isSMTP(); $mail->Host = $smtp->host; $mail->Port = $smtp->port; $mail->SMTPAuth = true; $mail->Username = $smtp->username; $mail->Password = $smtp->password; $mail->SMTPSecure = $smtp->secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS; $mail->CharSet = PHPMailer::CHARSET_UTF8; $mail->setFrom($smtp->fromAddress, $smtp->fromName); $mail->addAddress($to); $mail->Subject = $subject; $mail->isHTML(true); $mail->Body = $content['html']; $mail->AltBody = $content['text']; $mail->send();
            return true;
        } catch (\Throwable $exception) { return $this->fail($type, 'delivery_failure', $exception); }
    }

    private function fail(string $type, string $code, ?\Throwable $exception = null): false
    {
        error_log((string) json_encode(['workflow' => 'rm_audit', 'message_type' => $type, 'recipient_mode' => $type === 'completed' && (($this->config['corporate_test_mode'] ?? false) === true) ? 'test' : 'production', 'failure_code' => $code, 'exception_class' => $exception === null ? null : $exception::class], JSON_UNESCAPED_SLASHES));
        return false;
    }

    private function date(string $value): string
    {
        try { return (new \DateTimeImmutable($value))->format('F j, Y \a\t g:i A'); } catch (\Throwable) { return $value; }
    }
}
