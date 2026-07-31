<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class CorporateContextResult{public function __construct(public bool $created,public int $propertyId,public string $publicId){}}
