<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use DateTimeImmutable;
interface ClockInterface { public function now(): DateTimeImmutable; }
