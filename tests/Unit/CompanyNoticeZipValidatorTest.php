<?php
declare(strict_types=1);
use NpmGateway\Services\CompanyNoticeZipValidator;
use PHPUnit\Framework\TestCase;
final class CompanyNoticeZipValidatorTest extends TestCase
{
 private array $files=[];
 protected function tearDown():void{foreach($this->files as $file)if(is_file($file))unlink($file);}
 public function testValidZipIsAccepted():void{$path=$this->archive(['docs/readme.txt'=>'safe']);$result=(new CompanyNoticeZipValidator())->validate($path,'documents.zip',filesize($path));self::assertSame(1,$result['entries']);self::assertSame(4,$result['expanded_bytes']);}
 public function testMalformedZipIsRejected():void{$path=$this->temporary();file_put_contents($path,"PK\x03\x04broken");$this->reject($path,'Malformed archive accepted.');}
 public function testEncryptedZipIsRejectedWhenRuntimeSupportsEncryption():void{$path=$this->temporary();$zip=new ZipArchive();self::assertTrue($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE));$zip->addFromString('secret.txt','secret');if(!method_exists($zip,'setEncryptionName')){$zip->close();self::markTestSkipped('Zip encryption inspection is unavailable.');}$zip->setPassword('test');self::assertTrue($zip->setEncryptionName('secret.txt',ZipArchive::EM_AES_256));$zip->close();$this->reject($path,'Encrypted archive accepted.');}
 public function testTraversalAndExecutableEntriesAreRejected():void{foreach(['../escape.txt','folder\\..\\escape.txt','C:\\escape.txt','safe/run.exe'] as $name){$path=$this->archive([$name=>'x']);$this->reject($path,'Unsafe ZIP entry accepted: '.$name);}}
 public function testExcessiveEntryCountAndCompressionRatioAreRejected():void{$entries=[];for($i=0;$i<=CompanyNoticeZipValidator::MAX_ENTRIES;$i++)$entries['entries/'.$i.'.txt']='';$path=$this->archive($entries);$this->reject($path,'Excessive entry count accepted.');$path=$this->archive(['zeros.txt'=>str_repeat("\0",1048576)]);$this->reject($path,'Suspicious compression ratio accepted.');}
 public function testArchiveSafetyConstantsAreExplicit():void{self::assertSame(5000,CompanyNoticeZipValidator::MAX_ENTRIES);self::assertSame(2147483648,CompanyNoticeZipValidator::MAX_EXPANDED_BYTES);self::assertSame(524288000,CompanyNoticeZipValidator::MAX_ENTRY_BYTES);self::assertSame(100,CompanyNoticeZipValidator::MAX_COMPRESSION_RATIO);}
 private function archive(array $entries):string{$path=$this->temporary();$zip=new ZipArchive();self::assertTrue($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE));foreach($entries as $name=>$contents)$zip->addFromString($name,$contents);$zip->close();return $path;}
 private function temporary():string{$path=tempnam(sys_get_temp_dir(),'gateway_zip_');self::assertNotFalse($path);$this->files[]=$path;return $path;}
 private function reject(string $path,string $failure):void{try{(new CompanyNoticeZipValidator())->validate($path,'upload.zip',filesize($path));self::fail($failure);}catch(InvalidArgumentException){self::addToAssertionCount(1);}}
}
