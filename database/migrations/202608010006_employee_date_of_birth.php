<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface
{
    public function up(mysqli $connection):void
    {
        $connection->query('ALTER TABLE employees ADD COLUMN date_of_birth DATE NULL AFTER preferred_name');
    }
    public function down(mysqli $connection):void
    {
        $count=(int)$connection->query('SELECT COUNT(*) FROM employees WHERE date_of_birth IS NOT NULL')->fetch_row()[0];
        if($count>0)throw new RuntimeException('Cannot roll back Employee Date of Birth while employee date-of-birth values exist; rollback would destroy restricted employee data.');
        $connection->query('ALTER TABLE employees DROP COLUMN date_of_birth');
    }
};
