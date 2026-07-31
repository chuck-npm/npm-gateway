<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(mysqli $connection): void
    {
        $conflicts=[];
        foreach([
            'property'=>"SELECT property_id identity_id FROM employee_property_assignments WHERE assignment_type='property_manager' AND is_primary=1 AND ends_on IS NULL GROUP BY property_id HAVING COUNT(*)>1",
            'employee'=>"SELECT employee_id identity_id FROM employee_property_assignments WHERE assignment_type='property_manager' AND is_primary=1 AND ends_on IS NULL GROUP BY employee_id HAVING COUNT(*)>1",
        ] as $scope=>$sql){$result=$connection->query($sql);$ids=[];while($row=$result->fetch_assoc())$ids[]=(string)$row['identity_id'];$result->free();if($ids!==[])$conflicts[]=$scope.' IDs: '.implode(', ',$ids);}
        if($conflicts!==[])throw new RuntimeException('Active primary property manager conflicts must be resolved before migration: '.implode('; ',$conflicts));
        $connection->query(<<<'SQL'
ALTER TABLE properties
    ADD COLUMN prop_id INT UNSIGNED NULL COMMENT 'Permanent numeric NPM PropID; manually assigned during initial population.' AFTER public_id,
    ADD COLUMN office_phone VARCHAR(20) NULL COMMENT 'Normalized property office telephone number.' AFTER status,
    ADD COLUMN ivr_routing_email VARCHAR(254) NULL COMMENT 'Lowercase mailbox receiving IVR routing messages.' AFTER ivr_number,
    ADD UNIQUE KEY uq_properties_prop_id (prop_id),
    ADD CONSTRAINT chk_properties_prop_id_positive CHECK (prop_id IS NULL OR prop_id > 0),
    ADD CONSTRAINT chk_properties_ivr_routing_email_lowercase CHECK (ivr_routing_email IS NULL OR ivr_routing_email = LOWER(ivr_routing_email))
SQL);
        $connection->query(<<<'SQL'
ALTER TABLE employee_property_assignments
    ADD COLUMN active_primary_manager_property_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN assignment_type = 'property_manager' AND is_primary = 1 AND ends_on IS NULL THEN property_id ELSE NULL END) STORED,
    ADD COLUMN active_primary_manager_employee_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN assignment_type = 'property_manager' AND is_primary = 1 AND ends_on IS NULL THEN employee_id ELSE NULL END) STORED,
    ADD UNIQUE KEY uq_assignments_active_primary_manager_property (active_primary_manager_property_id),
    ADD UNIQUE KEY uq_assignments_active_primary_manager_employee (active_primary_manager_employee_id)
SQL);
    }

    public function down(mysqli $connection): void
    {
        $connection->query(<<<'SQL'
ALTER TABLE employee_property_assignments
    DROP INDEX uq_assignments_active_primary_manager_employee,
    DROP INDEX uq_assignments_active_primary_manager_property,
    DROP COLUMN active_primary_manager_employee_id,
    DROP COLUMN active_primary_manager_property_id
SQL);
        $connection->query(<<<'SQL'
ALTER TABLE properties
    DROP CHECK chk_properties_ivr_routing_email_lowercase,
    DROP CHECK chk_properties_prop_id_positive,
    DROP INDEX uq_properties_prop_id,
    DROP COLUMN ivr_routing_email,
    DROP COLUMN office_phone,
    DROP COLUMN prop_id
SQL);
    }
};
