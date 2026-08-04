<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Contracts\StorageObjectStoreInterface;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class TemporaryStorageDeletionService
{
 public function __construct(private readonly StorageObjectStoreInterface $objects,private readonly StorageAdapterInterface $adapter,private readonly AuditService $audits){}
 public function deleteAsUser(string $publicId,AuthenticatedUser $actor,string $at):bool{$row=$this->objects->findOwnedTemporary($publicId,$actor->id);if($row===null||$this->objects->hasNotificationLink((int)$row['id']))return false;$this->adapter->delete((string)$row['provider_container'],(string)$row['object_key']);if($this->adapter->exists((string)$row['provider_container'],(string)$row['object_key']))return false;if(!$this->objects->markDeletedByUser((int)$row['id'],$actor->id,$at))return false;$this->audits->record('storage.object_deleted',$actor->id,$actor->employeeId,$actor->publicId,'Temporary storage object deleted by its owner.',['storage_object_public_id'=>$publicId,'byte_size'=>(int)$row['byte_size'],'provider_deletion_result'=>'confirmed'],$at);return true;}
}
