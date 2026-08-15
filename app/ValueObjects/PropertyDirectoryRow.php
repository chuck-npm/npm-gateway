<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class PropertyDirectoryRow
{
    public function __construct(public string$publicId,public int $propId,public string $name,public string $address,public string $phone,public string $ivr,public string $manager){}
}
