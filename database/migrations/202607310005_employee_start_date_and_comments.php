<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\MigrationInterface;
return new class implements MigrationInterface
{
    public function up(mysqli $connection):void
    {
        $connection->query('ALTER TABLE employees DROP CHECK chk_employees_termination_dates');
        $connection->query('ALTER TABLE employees CHANGE COLUMN hire_date start_date DATE NOT NULL, ADD COLUMN comments TEXT NULL AFTER supervisor_employee_id, RENAME INDEX idx_employees_hire_date TO idx_employees_start_date');
        $connection->query('ALTER TABLE employees ADD CONSTRAINT chk_employees_termination_dates CHECK (termination_date IS NULL OR termination_date >= start_date)');
    }
    public function down(mysqli $connection):void
    {
        $count=(int)$connection->query('SELECT COUNT(*) FROM employees WHERE comments IS NOT NULL')->fetch_row()[0];
        if($count>0)throw new RuntimeException('Cannot roll back Employee Start Date and Comments while employee comments exist; rollback would discard HR data.');
        $connection->query('ALTER TABLE employees DROP CHECK chk_employees_termination_dates');
        $connection->query('ALTER TABLE employees CHANGE COLUMN start_date hire_date DATE NOT NULL, DROP COLUMN comments, RENAME INDEX idx_employees_start_date TO idx_employees_hire_date');
        $connection->query('ALTER TABLE employees ADD CONSTRAINT chk_employees_termination_dates CHECK (termination_date IS NULL OR termination_date >= hire_date)');
    }
};
