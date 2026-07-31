<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface
{
    public function up(mysqli $connection):void{$connection->query("ALTER TABLE properties MODIFY COLUMN ivr_number VARCHAR(20) NULL COMMENT 'Permanent advertising and IVR telephone number in normalized format; null only for approved contexts without an IVR.'");}
    public function down(mysqli $connection):void{$count=(int)$connection->query('SELECT COUNT(*) FROM properties WHERE ivr_number IS NULL')->fetch_row()[0];if($count>0)throw new RuntimeException('Cannot restore required IVR while properties with no IVR exist.');$connection->query("ALTER TABLE properties MODIFY COLUMN ivr_number VARCHAR(20) NOT NULL COMMENT 'Permanent advertising and IVR telephone number in normalized format.'");}
};
