<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class CredentialNotice
{
    public function __construct(
        public string $recipientEmail,
        public string $recipientName,
        public string $subject,
        public string $employeeName,
        public string $employeeNumber,
        public string $username,
        #[\SensitiveParameter] private string $generatedPassword,
        public string $jobTitle,
        public ?string $companyPhone,
        public string $createdAt
    ) {}
    public function generatedPassword(): string { return $this->generatedPassword; }
}
