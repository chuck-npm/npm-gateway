<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\GatewayStorageSchema;
use PHPUnit\Framework\TestCase;
final class GatewayStorageMigrationTest extends TestCase
{
 public function testApprovedTablesConstraintsAndReusableLinksAreDeclared():void{$sql=(string)file_get_contents(dirname(__DIR__,2).'/database/migrations/202608020010_gateway_storage.php');foreach(['CREATE TABLE storage_objects','CREATE TABLE notification_storage_objects','uq_storage_objects_provider_key','uq_notification_storage_objects_notice_object','attachment','embedded_image','temporary','published','deleted','sha256_hex','ON DELETE RESTRICT','Cannot roll back Gateway Storage'] as $required)self::assertStringContainsString($required,$sql);self::assertStringNotContainsString('uq_notification_storage_objects_object (',$sql);foreach(['104857600','1048576000','application/zip','wasabi'] as $businessRule)self::assertStringNotContainsString($businessRule,$sql);self::assertSame('202608020010_gateway_storage',GatewayStorageSchema::MIGRATION);}
}
