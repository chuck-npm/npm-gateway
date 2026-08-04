<?php
declare(strict_types=1);
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;

final class CompanyNoticeUploadResponseContractTest extends TestCase
{
 public function testEndpointReturnsSafeJsonCodesForExpectedFailureStages():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Http/Controllers/CompanyNoticeController.php');
  foreach(['csrf_failed','invalid_compose_context','upload_validation_failed','storage_unavailable','metadata_persistence_failed',"['ok'=>false,'error_code'=>\$code,'message'=>\$message]"] as $required)self::assertStringContainsString($required,$source);
  foreach(['object_key','provider_container','accessKey','secretKey','getTrace'] as $forbidden)self::assertStringNotContainsString($forbidden,substr($source,strpos($source,'public function upload('),strpos($source,'public function removeUpload')-strpos($source,'public function upload(')));
 }
 public function testComposeContextIsRejectedBeforeStorageUpload():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/CompanyNoticeAssetService.php');
  self::assertLessThan(strpos($source,'$this->storage->upload('),strpos($source,'$this->authorized('));
  self::assertStringContainsString("authorized(string \$composeId,AuthenticatedUser \$actor):array{if(\$this->composes->resolve(",$source);
 }
 public function testMetadataFailureRetainsCompensatingProviderCleanup():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/GatewayStorageService.php');
  $insert=strpos($source,'try{$id=$this->objects->insertTemporary(');$cleanup=strpos($source,'catch(\Throwable $e){try{$this->adapter->delete(');
  self::assertIsInt($insert);self::assertIsInt($cleanup);self::assertLessThan($cleanup,$insert);
  self::assertStringContainsString("throw new \\RuntimeException('The upload could not be saved safely.',0,\$e)",$source);
 }
}
