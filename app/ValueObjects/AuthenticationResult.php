<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class AuthenticationResult { public function __construct(public AuthenticatedUser $user,public SessionToken $session){} }
