<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Contracts\StorageObjectStoreInterface;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class GatewayStorageService
{
 public function __construct(private readonly StorageConfiguration $config,private readonly StorageAdapterInterface $adapter,private readonly StorageObjectStoreInterface $objects,private readonly StorageObjectKeyGenerator $keys,private readonly StorageUploadValidator $validator,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly AuditService $audits){}
 public function upload(array $file,string $role,AuthenticatedUser $owner,int $activeCount,int $activeBytes):array
 {
  $valid=$this->validator->validate($file,$role,$activeCount,$activeBytes);$publicId=$this->ids->generate();$prefix=$role==='embedded_image'?$this->config->imagePrefix:$this->config->attachmentPrefix;$key=$this->keys->generate($prefix,$valid['display_filename'],$this->clock->now());$stream=fopen($valid['path'],'rb');if(!is_resource($stream))throw new \RuntimeException('The upload stream could not be opened.');
  try{$provider=$this->adapter->put($this->config->container,$key,$stream,$valid['byte_size'],$valid['mime_type'],$valid['sha256']);}finally{fclose($stream);}if($provider->byteSize!==$valid['byte_size']||$provider->mimeType!==$valid['mime_type'])throw new \RuntimeException('Storage upload verification failed.');
  $head=$this->adapter->head($this->config->container,$key);if($head->byteSize!==$valid['byte_size']||$head->sha256!==$valid['sha256']){$this->adapter->delete($this->config->container,$key);throw new \RuntimeException('Storage upload verification failed.');}
  $at=$this->clock->now()->format('Y-m-d H:i:s');try{$id=$this->objects->insertTemporary(['public_id'=>$publicId,'provider'=>'wasabi','container'=>$this->config->container,'object_key'=>$key,'original_filename'=>$valid['original_filename'],'display_filename'=>$valid['display_filename'],'mime_type'=>$valid['mime_type'],'byte_size'=>$valid['byte_size'],'sha256'=>$valid['sha256'],'uploader_id'=>$owner->id,'owner_id'=>$owner->id,'created_at'=>$at]);}catch(\Throwable $e){try{$this->adapter->delete($this->config->container,$key);}catch(\Throwable){}throw new \RuntimeException('The upload could not be saved safely.',0,$e);}
  $this->audits->record('storage.object_uploaded',$owner->id,$owner->employeeId,$owner->publicId,'Storage object uploaded.',['storage_object_public_id'=>$publicId,'role'=>$role,'byte_size'=>$valid['byte_size'],'mime_category'=>strtok($valid['mime_type'],'/')],$at);
  return ['id'=>$id,'public_id'=>$publicId,'display_filename'=>$valid['display_filename'],'mime_type'=>$valid['mime_type'],'byte_size'=>$valid['byte_size'],'formatted_size'=>$this->size($valid['byte_size']),'type_label'=>$valid['type_label'],'role'=>$role,'dimensions'=>$valid['dimensions']];
 }
 public function open(array $object):mixed{return $this->adapter->openReadStream((string)$object['provider_container'],(string)$object['object_key']);}
 private function size(int $bytes):string{if($bytes<1024)return $bytes.' B';if($bytes<1048576)return number_format($bytes/1024,1).' KiB';return number_format($bytes/1048576,1).' MiB';}
}
