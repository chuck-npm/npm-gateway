<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class GeneratedCredential
{
    public function __construct(
        #[\SensitiveParameter] private string $plaintextPassword,
        public string $passwordHash,
        public string $algorithmName
    ) {}
    public function plaintextPassword(): string { return $this->plaintextPassword; }
}
