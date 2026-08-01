<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface CategoryAccessStoreInterface
{
    public function hasEffectiveMembership(int $userId,string $category):bool;
    public function findUserByUsername(string $username):?array;
    public function allUsers():array;
    public function memberships():array;
    public function grant(array $membership):void;
    public function revoke(int $userId,string $category):void;
}
