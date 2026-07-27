<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Contracts\CredentialNotifierInterface;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\ValueObjects\CredentialNotice;
final class DisabledCredentialNotifier implements CredentialNotifierInterface
{
    public function notify(CredentialNotice $notice): void
    {
        throw new CredentialNotificationException('No approved credential notifier is configured.');
    }
}
