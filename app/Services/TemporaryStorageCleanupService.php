<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Contracts\StorageObjectStoreInterface;
final class TemporaryStorageCleanupService
{
 public function __construct(private readonly StorageObjectStoreInterface $objects,private readonly StorageAdapterInterface $adapter,private readonly AuditService $audits,private readonly \DateTimeZone $timezone=new \DateTimeZone('UTC')){}
 public function run(?\DateTimeImmutable $now=null,int $hours=24):array{$now=($now??new \DateTimeImmutable('now',$this->timezone))->setTimezone($this->timezone);$before=$now->modify('-'.max(1,$hours).' hours')->format('Y-m-d H:i:s');$result=['examined'=>0,'deleted'=>0,'skipped'=>0,'failed'=>0];foreach($this->objects->cleanupCandidates($before) as $row){$result['examined']++;if(($row['lifecycle_state']??'')!=='temporary'||$this->objects->hasNotificationLink((int)$row['id'])){$result['skipped']++;continue;}try{$this->adapter->delete((string)$row['provider_container'],(string)$row['object_key']);if($this->adapter->exists((string)$row['provider_container'],(string)$row['object_key']))throw new \RuntimeException('Storage deletion was not confirmed.');$at=$now->format('Y-m-d H:i:s');if(!$this->objects->markDeletedBySystem((int)$row['id'],$at))throw new \RuntimeException('Storage lifecycle transition failed.');$this->audits->recordSystem('storage.object_deleted','storage_object',(int)$row['id'],(string)$row['public_id'],'Expired temporary storage object deleted by system cleanup.',['storage_object_public_id'=>$row['public_id'],'byte_size'=>(int)$row['byte_size'],'cleanup_threshold_hours'=>$hours,'provider_deletion_result'=>'confirmed'],$at);$result['deleted']++;}catch(\Throwable){$result['failed']++;}}return $result;}
}
