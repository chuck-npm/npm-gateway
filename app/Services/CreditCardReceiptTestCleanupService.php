<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\Repositories\StorageObjectRepository;
final class CreditCardReceiptTestCleanupService
{
 public function __construct(private readonly StorageConfiguration $config,private readonly StorageAdapterInterface $adapter,private readonly StorageObjectRepository $objects){}
 /** @return array{prefix:string,keys:list<string>,referenced:list<string>} */
 public function preview():array
 {
  if(!$this->config->creditCardReceiptRealStorageTesting)throw new \RuntimeException('Receipt real-storage testing is not enabled.');
  $prefix=$this->config->creditCardReceiptUploadPrefix();
  $keys=$this->adapter->listPrefix($this->config->container,$prefix);
  foreach($keys as $key)if(!str_starts_with($key,$prefix))throw new \RuntimeException('Storage provider returned an object outside the approved test prefix.');
  return ['prefix'=>$prefix,'keys'=>$keys,'referenced'=>$this->objects->keysRecordedUnderPrefix($this->config->container,$prefix)];
 }
 /** @param list<string> $expectedKeys */
 public function deleteUnreferenced(array $expectedKeys):int
 {
  $preview=$this->preview();if($preview['referenced']!==[])throw new \RuntimeException('Cleanup refused because database-recorded test objects exist.');if($preview['keys']!==$expectedKeys)throw new \RuntimeException('Cleanup refused because the provider object list changed after preview.');foreach($expectedKeys as $key)$this->adapter->delete($this->config->container,$key);error_log('Credit card receipt test cleanup completed: deleted '.count($expectedKeys).' unreferenced object(s).');return count($expectedKeys);
 }
}
