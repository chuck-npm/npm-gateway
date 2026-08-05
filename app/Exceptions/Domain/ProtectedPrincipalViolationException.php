<?php
declare(strict_types=1);
namespace NpmGateway\Exceptions\Domain;
final class ProtectedPrincipalViolationException extends \InvalidArgumentException
{
    public function __construct(public readonly string $reasonCode='protected_baseline_required'){parent::__construct('This protected Gateway administrator cannot be deactivated or removed.');}
}
