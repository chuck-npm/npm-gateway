<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface PropertyStoreInterface
{
    public function propIdExists(int $propId):bool;
    public function propertyCodeExists(string $code):bool;
    public function slugExists(string $slug):bool;
    public function managerEmailExists(string $email):bool;
    public function ivrNumberExists(string $number):bool;
    public function insert(array $property):int;
}
