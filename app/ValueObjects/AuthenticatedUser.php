<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class AuthenticatedUser { public function __construct(public int $id,public int $employeeId,public string $publicId,public string $employeePublicId,public string $username,public string $displayName){} }
