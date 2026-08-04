<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeAnnouncement { public function __construct(public string $sourcePublicId,public array $payload,public string $title='New Employee',public string $summary='A new employee has joined NPM Properties.'){} }
