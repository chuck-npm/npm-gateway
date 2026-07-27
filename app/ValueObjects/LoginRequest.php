<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class LoginRequest { public function __construct(public string $username,#[\SensitiveParameter] private string $password){} public function password():string{return $this->password;} }
