<?php
declare(strict_types=1);
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;

final class Commit015TeardownSafetyTest extends TestCase
{
 public function testGuardedE2eInitializesResourcesAndCleansOnlyInitializedResources():void
 {
  $source=(string)file_get_contents(dirname(__DIR__).'/Integration/Commit015EndToEndIntegrationTest.php');
  foreach(['private ?mysqli $db=null','private ?StorageAdapterInterface $adapter=null','private ?StorageConfiguration $config=null','if($this->adapter!==null&&$this->config!==null)','if($this->db!==null)$this->cleanDb()','finally{','if($this->db!==null)','if($failure!==null)throw $failure'] as $required)self::assertStringContainsString($required,$source);
  self::assertLessThan(strpos($source,'ServiceProvider::build'),strpos($source,"markTestSkipped('Guarded Commit 015 E2E is not enabled.')"));
 }

 public function testMandatoryTestProfileAndProviderPrefixGatesRemainStrict():void
 {
  $source=(string)file_get_contents(dirname(__DIR__).'/Integration/Commit015EndToEndIntegrationTest.php');
  self::assertStringContainsString("foreach(['application','migration'] as \$profile)self::assertSame('npmgateway_test'",$source);
  self::assertStringContainsString("self::assertSame('company_notices/test/',getenv('WASABI_TEST_PREFIX'))",$source);
  self::assertStringContainsString("assertStringStartsWith('company_notices/test/',(string)getenv('WASABI_COMPANY_NOTICE_ATTACHMENTS_PREFIX'))",$source);
  self::assertStringContainsString("assertStringStartsWith('company_notices/test/',(string)getenv('WASABI_COMPANY_NOTICE_IMAGES_PREFIX'))",$source);
  self::assertStringNotContainsString('company_notices/attachments/',$source);
  self::assertStringNotContainsString('company_notices/images/',$source);
 }
}
