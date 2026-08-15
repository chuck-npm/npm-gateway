<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface PropertyStoreInterface
{
    public function propIdExists(int $propId,?int $excludeId=null):bool;
    public function propertyCodeExists(string $code,?int $excludeId=null):bool;
    public function slugExists(string $slug,?int $excludeId=null):bool;
    public function findByPublicId(string $publicId):?array;
    public function insert(array $property):int;
    public function update(int $id,array $property):void;
}
