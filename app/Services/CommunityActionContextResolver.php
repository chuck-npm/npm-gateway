<?php
declare(strict_types=1);
namespace NpmGateway\Services;

use NpmGateway\Exceptions\Domain\CommunityActionPropertyForbiddenException;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyNotFoundException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\ValueObjects\CommunityActionContext;

final readonly class CommunityActionContextResolver
{
    public function __construct(private PropertyAccessService $propertyAccess) {}

    public function resolve(AuthenticatedRequestContext $requestContext, string $propertySlug): CommunityActionContext
    {
        $result = $this->propertyAccess->resolveAccessibleProperty($requestContext, $propertySlug);
        if ($result['status'] === 'not_found') throw new CommunityActionPropertyNotFoundException();
        if ($result['status'] !== 'authorized') throw new CommunityActionPropertyForbiddenException();
        $property = $result['property'];
        return new CommunityActionContext(
            $requestContext->user,
            $requestContext->user->id,
            $requestContext->user->publicId,
            $requestContext->user->employeeId,
            $requestContext->user->employeePublicId,
            (int) $property['id'],
            (string) $property['public_id'],
            (string) $property['slug'],
            (string) $property['property_code'],
            (string) $property['display_name'],
            true,
        );
    }
}
