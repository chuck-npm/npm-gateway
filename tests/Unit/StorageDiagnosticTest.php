<?php
declare(strict_types=1);
use NpmGateway\Console\StorageDiagnosticCommand;
use NpmGateway\Contracts\StorageAdapterInterface;
use NpmGateway\ValueObjects\StorageProviderHead;
use NpmGateway\ValueObjects\StorageProviderObject;
use PHPUnit\Framework\TestCase;
final class StorageDiagnosticTest extends TestCase
{
 private function config():array{return ['endpoint'=>'https://s3.us-east-1.wasabisys.com','region'=>'us-east-1','container'=>'test-bucket','access_key'=>'test-access','secret_key'=>'test-secret','attachment_prefix'=>'company_notices/attachments/','image_prefix'=>'company_notices/images/','test_prefix'=>'company_notices/test/'];}
 private function profiles(array $replace=[]):array{return array_replace(['application'=>'npmgateway_test','migration'=>'npmgateway_test','environment'=>'local'],$replace);}
 private function adapter(int &$calls):StorageAdapterInterface{return new class($calls) implements StorageAdapterInterface{private bool $exists=false;private string $body='';public function __construct(private int &$calls){}public function put(string $c,string $k,mixed $s,int $z,string $m,string $h):StorageProviderObject{$this->calls++;$this->body=stream_get_contents($s);$this->exists=true;return new StorageProviderObject($z,$m);}public function openReadStream(string $c,string $k):mixed{$this->calls++;$s=fopen('php://temp','w+b');fwrite($s,$this->body);rewind($s);return $s;}public function exists(string $c,string $k):bool{$this->calls++;return $this->exists;}public function delete(string $c,string $k):void{$this->calls++;$this->exists=false;}public function head(string $c,string $k):StorageProviderHead{$this->calls++;return new StorageProviderHead(strlen($this->body),'text/plain',null,hash('sha256',$this->body));}public function listPrefix(string $c,string $p):array{$this->calls++;return $this->exists?[$p.'object']:[];}};}
 public function testGuardRejectsAnyNormalDatabaseBeforeProvider():void{$calls=0;$adapter=$this->adapter($calls);foreach([['application'=>'npmgateway'],['migration'=>'other'],['environment'=>'production']] as $override)self::assertSame(2,StorageDiagnosticCommand::run($this->config(),$this->profiles($override),$adapter)['exit_code']);self::assertSame(0,$calls);}
 public function testGuardRejectsUnsafePrefixAndEndpoint():void{$calls=0;$adapter=$this->adapter($calls);foreach([array_replace($this->config(),['test_prefix'=>'company_notices/attachments/']),array_replace($this->config(),['endpoint'=>'https://example.test'])] as $config)self::assertSame(2,StorageDiagnosticCommand::run($config,$this->profiles(),$adapter)['exit_code']);self::assertSame(0,$calls);}
 public function testDiagnosticUploadsHeadsReadsDeletesAndConfirmsNoResidue():void{$calls=0;$result=StorageDiagnosticCommand::run($this->config(),$this->profiles(),$this->adapter($calls));self::assertSame(0,$result['exit_code']);self::assertSame(7,$calls);self::assertStringContainsString('Object count under company_notices/test/: 0',$result['stdout']);self::assertStringContainsString('Residue: none',$result['stdout']);self::assertStringNotContainsString('test-secret',$result['stdout']);}
}
