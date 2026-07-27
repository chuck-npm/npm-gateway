<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\CredentialNotifierInterface;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\Exceptions\Domain\UnsafeBootstrapEnvironmentException;
use NpmGateway\ValueObjects\CredentialNotice;
final class NotificationService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly CredentialNotifierInterface $notifier,
        private readonly array $config
    ) {}
    public function validateMode(bool $skip): void
    {
        $environment = strtolower((string) ($this->config['environment'] ?? 'production'));
        if ($skip) {
            if (!in_array($environment, ['local', 'testing'], true) || ($this->config['allow_local_fallback'] ?? false) !== true) {
                throw new UnsafeBootstrapEnvironmentException('Notification bypass is not permitted in this environment.');
            }
            return;
        }
        if (($this->config['configured'] ?? false) !== true) {
            throw new UnsafeBootstrapEnvironmentException('Approved credential notification transport is not configured.');
        }
    }
    public function send(CredentialNotice $notice, bool $skip): string
    {
        $this->validateMode($skip);
        if ($skip) { return 'skipped-local'; }
        if (stripos($notice->subject, 'secure') === false) {
            throw new CredentialNotificationException('Credential notification subject must contain secure.');
        }
        try { $this->notifier->notify($notice); }
        catch (\Throwable $exception) { throw new CredentialNotificationException('Credential notification failed.', 0, $exception); }
        return 'sent';
    }
}
