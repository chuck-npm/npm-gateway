<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\ValueObjects\AuthenticatedUser;
final readonly class CallLogAccessPolicy
{
 public function __construct(private ProtectedPrincipalConfig $principal){}
 public function allows(AuthenticatedUser $user):bool{return $this->principal->configured()&&hash_equals($this->principal->userPublicId,$user->publicId);}
}
