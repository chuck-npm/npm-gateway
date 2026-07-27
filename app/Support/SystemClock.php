<?php
declare(strict_types=1);
namespace NpmGateway\Support;
use DateTimeImmutable;
use DateTimeZone;
use NpmGateway\Contracts\ClockInterface;
final class SystemClock implements ClockInterface
{
    public function __construct(private readonly DateTimeZone $timezone) {}
    public function now(): DateTimeImmutable { return new DateTimeImmutable('now', $this->timezone); }
}
