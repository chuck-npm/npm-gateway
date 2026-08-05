<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface PropertyAccessStoreInterface
{
 public function hasEffectiveAccess(int $userId,int $propertyId):bool;
 public function accessibleActiveProperties(int $userId):array;
 public function activePropertyBySlug(string $slug):?array;
 public function employeesForMatrix():array;
 public function activeProperties():array;
 public function memberships():array;
 public function grant(array $grant):void;
 public function revoke(int $userId,int $propertyId):void;
}
