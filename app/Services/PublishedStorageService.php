<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Repositories\StorageObjectRepository;
final class PublishedStorageService
{
 public function __construct(private readonly StorageObjectRepository $objects,private readonly CorporateAccessService $access,private readonly GatewayStorageService $storage){}
 public function authorized(string $publicId,AuthenticatedRequestContext $context,string $role):?array{$publisher=$this->access->canAccessCategory($context,'company-notices');return $this->objects->findPublishedAuthorized($publicId,$context->user->id,$publisher,$role);}
 public function open(array $object):mixed{return $this->storage->open($object);}
}
