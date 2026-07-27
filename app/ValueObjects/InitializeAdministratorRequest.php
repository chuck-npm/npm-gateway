<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class InitializeAdministratorRequest
{
    public function __construct(
        public string $employeeNumber,
        public string $firstName,
        public string $lastName,
        public string $jobTitle,
        public string $businessEmail,
        public ?string $personalEmail,
        public ?string $companyPhone,
        public ?string $personalPhone,
        public string $username,
        public bool $skipNotification = false
    ) {}
}
