<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class NotificationCount { public function __construct(public int $outstanding){} public function label():string{return $this->outstanding===0?'No outstanding notices':$this->outstanding.' require acknowledgment';} }
