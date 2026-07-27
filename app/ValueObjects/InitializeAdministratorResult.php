<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class InitializeAdministratorResult
{
    public function __construct(
        public string $employeePublicId,
        public string $userPublicId,
        public string $employeeNumber,
        public string $username,
        #[\SensitiveParameter] private string $generatedPassword,
        public string $credentialNotificationStatus,
        public ?string $credentialNotificationErrorSummary,
        public string $initializedAt
    ) {}
    public function generatedPassword(): string { return $this->generatedPassword; }
}
