<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class SessionToken { public function __construct(#[\SensitiveParameter] private string $rawToken,public string $publicId){} public function reveal():string{return $this->rawToken;} }
