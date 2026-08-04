<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\StorageSystemCleanupActorSchema as Schema;
use PHPUnit\Framework\TestCase;
final class StorageSystemCleanupActorMigrationTest extends TestCase
{
 public function testMigrationAltersOnlyApprovedConstraintAndGuardsRollback():void{$source=(string)file_get_contents(dirname(__DIR__,2).'/database/migrations/202608020011_storage_system_cleanup_actor.php');foreach(['chk_storage_objects_lifecycle_metadata','deleted_at IS NOT NULL','system-deleted storage history exists','assertClause'] as $required)self::assertStringContainsString($required,$source);foreach(['ADD COLUMN','DROP COLUMN','CREATE TABLE','DROP TABLE','ADD INDEX','DROP INDEX','FOREIGN KEY','INSERT INTO','UPDATE storage_objects','DELETE FROM'] as $forbidden)self::assertStringNotContainsString($forbidden,$source);self::assertStringContainsString("deleted_by_user_id` is not null",Schema::BEFORE);self::assertStringNotContainsString("deleted_by_user_id` is not null",substr(Schema::AFTER,strpos(Schema::AFTER,"'deleted'")));}
}
