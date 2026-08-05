<?php
declare(strict_types=1);
namespace NpmGateway\Exceptions\Domain;
final class IneligibleNotificationRecipientException extends \DomainException
{
 public function __construct(){parent::__construct('Notification recipient resolution violated platform eligibility policy.');}
}
