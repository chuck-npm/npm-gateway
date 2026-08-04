<?php
declare(strict_types=1);
namespace NpmGateway\Storage;
use Aws\Exception\AwsException;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Exceptions\Domain\StorageProviderException;
use NpmGateway\ValueObjects\StorageProviderHead;
use NpmGateway\ValueObjects\StorageProviderObject;
final class WasabiStorageAdapter implements StorageAdapterInterface
{
 public function __construct(private readonly S3Client $client){}
 public static function create(string $endpoint,string $region,string $accessKey,string $secretKey):self{return new self(new S3Client(['version'=>'latest','region'=>$region,'endpoint'=>rtrim($endpoint,'/'),'use_path_style_endpoint'=>true,'credentials'=>['key'=>$accessKey,'secret'=>$secretKey],'http'=>['verify'=>true]]));}
 public function put(string $container,string $objectKey,mixed $stream,int $byteSize,string $mimeType,string $sha256):StorageProviderObject
 {
  if(!is_resource($stream))throw new StorageProviderException('Storage upload stream is invalid.');try{$uploader=new ObjectUploader($this->client,$container,$objectKey,$stream,'private',['params'=>['ContentType'=>$mimeType,'ContentLength'=>$byteSize,'Metadata'=>['sha256'=>$sha256],'IfNoneMatch'=>'*']]);$result=$uploader->upload();return new StorageProviderObject($byteSize,$mimeType,is_string($result['ETag']??null)?trim($result['ETag'],'"'):null);}catch(\Throwable $e){throw new StorageProviderException('Storage upload failed safely.',0,$e);}
 }
 public function openReadStream(string $container,string $objectKey):mixed{try{$result=$this->client->getObject(['Bucket'=>$container,'Key'=>$objectKey,'@http'=>['stream'=>true]]);$body=$result['Body'];if(method_exists($body,'detach')){$stream=$body->detach();if(is_resource($stream))return $stream;}$stream=fopen('php://temp','w+b');while(!$body->eof())fwrite($stream,$body->read(8192));rewind($stream);return $stream;}catch(\Throwable $e){throw new StorageProviderException('Storage read failed safely.',0,$e);}}
 public function exists(string $container,string $objectKey):bool{try{return $this->client->doesObjectExistV2($container,$objectKey);}catch(\Throwable $e){throw new StorageProviderException('Storage verification failed safely.',0,$e);}}
 public function delete(string $container,string $objectKey):void{try{$this->client->deleteObject(['Bucket'=>$container,'Key'=>$objectKey]);$this->client->waitUntil('ObjectNotExists',['Bucket'=>$container,'Key'=>$objectKey]);}catch(\Throwable $e){throw new StorageProviderException('Storage deletion failed safely.',0,$e);}}
 public function head(string $container,string $objectKey):StorageProviderHead{try{$result=$this->client->headObject(['Bucket'=>$container,'Key'=>$objectKey]);return new StorageProviderHead((int)$result['ContentLength'],(string)($result['ContentType']??'application/octet-stream'),is_string($result['ETag']??null)?trim($result['ETag'],'"'):null,is_string($result['Metadata']['sha256']??null)?$result['Metadata']['sha256']:null);}catch(\Throwable $e){throw new StorageProviderException('Storage metadata verification failed safely.',0,$e);}}
 public function listPrefix(string $container,string $prefix):array{try{$keys=[];$token=null;do{$args=['Bucket'=>$container,'Prefix'=>$prefix,'MaxKeys'=>1000];if($token!==null)$args['ContinuationToken']=$token;$result=$this->client->listObjectsV2($args);foreach($result['Contents']??[] as $object)if(is_string($object['Key']??null))$keys[]=$object['Key'];$token=($result['IsTruncated']??false)&&is_string($result['NextContinuationToken']??null)?$result['NextContinuationToken']:null;}while($token!==null);return $keys;}catch(\Throwable $e){throw new StorageProviderException('Storage prefix enumeration failed safely.',0,$e);}}
}
