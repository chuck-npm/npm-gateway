<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
use DateTimeImmutable;
final readonly class ClientContext { public function __construct(public string $ipAddress,public ?string $userAgent,public DateTimeImmutable $now){} }
