<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\StorageProviderHead;
use NpmGateway\ValueObjects\StorageProviderObject;
interface StorageAdapterInterface
{
 /** @param resource $stream */ public function put(string $container,string $objectKey,mixed $stream,int $byteSize,string $mimeType,string $sha256):StorageProviderObject;
 /** @return resource */ public function openReadStream(string $container,string $objectKey):mixed;
 public function exists(string $container,string $objectKey):bool;
 public function delete(string $container,string $objectKey):void;
 public function head(string $container,string $objectKey):StorageProviderHead;
 /** @return list<string> */ public function listPrefix(string $container,string $prefix):array;
}
