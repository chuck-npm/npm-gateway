<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\StorageObjectStoreInterface;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class CompanyNoticeAssetService
{
 public function __construct(private readonly CompanyNoticeComposeStore $composes,private readonly StorageObjectStoreInterface $objects,private readonly GatewayStorageService $storage,private readonly TemporaryStorageDeletionService $deletion){}
 public function upload(string $composeId,array $file,string $role,AuthenticatedUser $actor):array{$assets=$this->authorized($composeId,$actor);$bytes=array_sum(array_map(static fn(array $r):int=>(int)$r['byte_size'],$assets));$safe=$this->storage->upload($file,$role,$actor,count($assets),$bytes);if(!$this->composes->select($composeId,$actor->id,$safe['public_id'],$role))throw new \RuntimeException('The compose context expired.');unset($safe['id']);$safe['url']='/company-notices/uploads/'.$safe['public_id'].'/'.($role==='embedded_image'?'preview':'download');return $safe;}
 public function remove(string $composeId,string $publicId,AuthenticatedUser $actor,string $at):bool{if(!isset($this->composes->assets($composeId,$actor->id)[$publicId]))return false;if(!$this->deletion->deleteAsUser($publicId,$actor,$at))return false;$this->composes->remove($composeId,$actor->id,$publicId);return true;}
 public function authorized(string $composeId,AuthenticatedUser $actor):array{if($this->composes->resolve($composeId,$actor->id)===null)throw new \InvalidArgumentException('The compose context is unavailable.');$selected=$this->composes->assets($composeId,$actor->id);$rows=[];$bytes=0;foreach($selected as $publicId=>$role){$row=$this->objects->findOwnedTemporary((string)$publicId,$actor->id);if($row===null)throw new \InvalidArgumentException('A selected upload is no longer available.');$row['asset_role']=$role;$rows[]=$row;$bytes+=(int)$row['byte_size'];}if(count($rows)>CompanyNoticeAssetPolicy::MAX_ASSETS||$bytes>CompanyNoticeAssetPolicy::MAX_TOTAL_BYTES)throw new \InvalidArgumentException('The selected uploads exceed the allowed limits.');return $rows;}
 public function findTemporary(string $composeId,string $publicId,AuthenticatedUser $actor,?string $expectedRole=null):?array{$selected=$this->composes->assets($composeId,$actor->id);if(!isset($selected[$publicId])||($expectedRole!==null&&$selected[$publicId]!==$expectedRole))return null;return $this->objects->findOwnedTemporary($publicId,$actor->id);}
 public function releasePublished(string $composeId,AuthenticatedUser $actor,array $published):void{foreach($published as $asset)$this->composes->remove($composeId,$actor->id,(string)$asset['public_id']);}
}
