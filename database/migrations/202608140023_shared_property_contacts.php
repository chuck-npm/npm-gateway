<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\{MigrationException,MigrationInterface};

return new class implements MigrationInterface
{
    public function up(mysqli $connection):void
    {
        foreach(['manager_email','ivr_number']as$column){$unique='uq_properties_'.$column;$ordinary='idx_properties_'.$column;$index=$this->index($connection,$unique);if($index!==[['column'=>$column,'non_unique'=>0]])throw new MigrationException("{$unique} is missing or has an unexpected definition.");if($this->index($connection,$ordinary)!==null)throw new MigrationException("Replacement index {$ordinary} already exists.");}
        $connection->query('ALTER TABLE properties DROP INDEX uq_properties_manager_email, DROP INDEX uq_properties_ivr_number, ADD INDEX idx_properties_manager_email (manager_email), ADD INDEX idx_properties_ivr_number (ivr_number)');
    }

    public function down(mysqli $connection):void
    {
        foreach(['manager_email','ivr_number']as$column){$duplicates=(int)$connection->query("SELECT COUNT(*) FROM (SELECT {$column} FROM properties WHERE {$column} IS NOT NULL GROUP BY {$column} HAVING COUNT(*)>1) shared_contacts")->fetch_row()[0];if($duplicates>0)throw new MigrationException("Cannot restore {$column} uniqueness while shared values exist.");$ordinary='idx_properties_'.$column;if($this->index($connection,$ordinary)!==[['column'=>$column,'non_unique'=>1]])throw new MigrationException("Expected non-unique index {$ordinary} is missing or invalid.");}
        $connection->query('ALTER TABLE properties DROP INDEX idx_properties_manager_email, DROP INDEX idx_properties_ivr_number, ADD UNIQUE INDEX uq_properties_manager_email (manager_email), ADD UNIQUE INDEX uq_properties_ivr_number (ivr_number)');
    }

    private function index(mysqli $connection,string $name):?array
    {
        $statement=$connection->prepare("SELECT COLUMN_NAME,NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='properties' AND INDEX_NAME=? ORDER BY SEQ_IN_INDEX");
        $statement->bind_param('s',$name);$statement->execute();$rows=$statement->get_result()->fetch_all(MYSQLI_ASSOC);$statement->close();
        if($rows===[])return null;
        return array_map(static fn(array $row):array=>['column'=>(string)$row['COLUMN_NAME'],'non_unique'=>(int)$row['NON_UNIQUE']],$rows);
    }
};
