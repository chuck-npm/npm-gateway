<?php
declare(strict_types=1);
namespace NpmGateway\Http;
use NpmGateway\ValueObjects\AuthenticatedUser;
final readonly class AuthenticatedRequestContext { public function __construct(public AuthenticatedUser $user,public string $rawToken){} }
