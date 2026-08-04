<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface StorageObjectStoreInterface
{
 public function insertTemporary(array $metadata):int;
 public function findByPublicId(string $publicId):?array;
 public function findOwnedTemporary(string $publicId,int $ownerId):?array;
 public function markDeletedByUser(int $id,int $actorId,string $at):bool;
 public function markDeletedBySystem(int $id,string $at):bool;
 public function markPublished(int $id,string $at):bool;
 public function cleanupCandidates(string $before,int $limit=100):array;
 public function hasNotificationLink(int $id):bool;
}
