<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;

final readonly class CommunityActionContext
{
    public function __construct(
        public AuthenticatedUser $user,
        public int $userId,
        public string $userPublicId,
        public int $employeeId,
        public string $employeePublicId,
        public int $propertyId,
        public string $propertyPublicId,
        public string $propertySlug,
        public string $propertyCode,
        public string $propertyDisplayName,
        public bool $accessVerified,
    ) {}
}
