<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class StorageProviderObject{public function __construct(public int $byteSize,public string $mimeType,public ?string $etag=null){}}
