<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface EmployeeStoreInterface {
    public function employeeNumberExists(string $employeeNumber): bool;
    /** @param array<string, mixed> $employee */
    public function insert(array $employee): int;
}
