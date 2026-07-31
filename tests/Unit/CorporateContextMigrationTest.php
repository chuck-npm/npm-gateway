<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationInterface;
use PHPUnit\Framework\TestCase;
final class CorporateContextMigrationTest extends TestCase
{
    public function testMigrationMakesOnlyIvrNumberNullableAndHasGuardedRollback():void{$path=dirname(__DIR__,2).'/database/migrations/202607310004_corporate_context.php';$sql=(string)file_get_contents($path);self::assertTrue(MigrationDiscovery::isValidFilename(basename($path)));self::assertInstanceOf(MigrationInterface::class,require $path);self::assertStringContainsString('MODIFY COLUMN ivr_number VARCHAR(20) NULL',$sql);self::assertStringContainsString('WHERE ivr_number IS NULL',$sql);self::assertStringContainsString('MODIFY COLUMN ivr_number VARCHAR(20) NOT NULL',$sql);self::assertStringNotContainsString('INSERT INTO properties',$sql);}
}
