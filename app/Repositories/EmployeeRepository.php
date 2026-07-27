<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\EmployeeStoreInterface;
final class EmployeeRepository implements EmployeeStoreInterface
{
    public function __construct(private readonly mysqli $connection) {}
    public function employeeNumberExists(string $employeeNumber): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM employees WHERE employee_number = ? LIMIT 1');
        $statement->bind_param('s', $employeeNumber);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
    public function insert(array $employee): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO employees
             (public_id, employee_number, employee_class, first_name, last_name, business_email,
              personal_email, company_phone, personal_phone, job_title, employment_status, hire_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'ssssssssssss',
            $employee['public_id'], $employee['employee_number'], $employee['employee_class'],
            $employee['first_name'], $employee['last_name'], $employee['business_email'],
            $employee['personal_email'], $employee['company_phone'], $employee['personal_phone'],
            $employee['job_title'], $employee['employment_status'], $employee['hire_date']
        );
        $statement->execute();
        $id = $this->connection->insert_id;
        $statement->close();
        return $id;
    }
}
