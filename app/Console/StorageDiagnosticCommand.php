<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Services\StorageConfiguration;
use NpmGateway\Services\StorageObjectKeyGenerator;
use NpmGateway\Storage\WasabiStorageAdapter;
final class StorageDiagnosticCommand
{
 public static function run(array $storage,array $profiles,?StorageAdapterInterface $adapter=null,?StorageObjectKeyGenerator $keys=null):array
 {
  if(($profiles['application']??'')!=='npmgateway_test'||($profiles['migration']??'')!=='npmgateway_test')return self::blocked('Both database profiles must resolve to npmgateway_test.');if(!in_array((string)($profiles['environment']??''),['local','development'],true))return self::blocked('Storage tests are restricted to local/development environments.');try{$config=StorageConfiguration::fromArray($storage);}catch(\Throwable){return self::blocked('Wasabi storage configuration is incomplete or invalid.');}if($config->testPrefix!=='company_notices/test/')return self::blocked('The Wasabi test prefix is not the approved isolated prefix.');if(!preg_match('/(^|\.)wasabisys\.com$/D',$config->endpointHost()))return self::blocked('The Wasabi endpoint is not approved.');$keys??=new StorageObjectKeyGenerator();$key=$keys->generate($config->testPrefix,'gateway-storage-diagnostic.txt');if(!preg_match('#^company_notices/test/[0-9]{8}_[0-9]{6}_[A-F0-9]{8}_gateway_storage_diagnostic\.txt$#D',$key))return self::blocked('Generated diagnostic object key is unsafe.');$adapter??=WasabiStorageAdapter::create($config->endpoint,$config->region,$config->accessKey,$config->secretKey);
  try{if($adapter->listPrefix($config->container,$config->testPrefix)!==[])return self::blocked('The isolated Wasabi test prefix is not empty before the diagnostic.');}catch(\Throwable){return self::blocked('The isolated Wasabi test prefix could not be enumerated safely.');}
  $body='NPM Gateway isolated storage diagnostic '.bin2hex(random_bytes(8));$stream=fopen('php://temp','w+b');fwrite($stream,$body);rewind($stream);$sha=hash('sha256',$body);$uploaded=false;
  try{$adapter->put($config->container,$key,$stream,strlen($body),'text/plain',$sha);$uploaded=true;$head=$adapter->head($config->container,$key);if($head->byteSize!==strlen($body)||$head->sha256!==$sha)throw new \RuntimeException();$read=$adapter->openReadStream($config->container,$key);$hash=hash_init('sha256');hash_update_stream($hash,$read);if(hash_final($hash)!==$sha)throw new \RuntimeException();$adapter->delete($config->container,$key);$uploaded=false;if($adapter->exists($config->container,$key)||$adapter->listPrefix($config->container,$config->testPrefix)!==[])throw new \RuntimeException();return ['exit_code'=>0,'stdout'=>"Wasabi storage test: passed\nObject key format: approved isolated prefix and UTC timestamp\nUpload/head/read/hash verified: yes\nDelete verified: yes\nObject count under company_notices/test/: 0\nResidue: none\n",'stderr'=>''];}catch(\Throwable){if($uploaded){try{$adapter->delete($config->container,$key);}catch(\Throwable){}}return ['exit_code'=>1,'stdout'=>'','stderr'=>"Wasabi storage test failed safely; verify the isolated prefix is empty before retrying.\n"];}finally{fclose($stream);}
 }
 private static function blocked(string $message):array{return ['exit_code'=>2,'stdout'=>'','stderr'=>$message."\nNo storage request was made.\n"];}
}
