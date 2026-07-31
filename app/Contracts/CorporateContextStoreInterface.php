<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface CorporateContextStoreInterface
{
    public function findCorporateIdentifierMatches():array;
    public function insertCorporate(array $property):int;
}
