<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Contracts\HrEmployeeNotifierInterface;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\ValueObjects\HrEmployeeNotice;
final class DisabledHrEmployeeNotifier implements HrEmployeeNotifierInterface{public function notify(HrEmployeeNotice $notice):void{throw new CredentialNotificationException('No approved HR employee notification transport is configured.');}}
