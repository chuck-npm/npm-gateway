<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface HrEmployeeStoreInterface extends EmployeeStoreInterface
{
    public function highestEmployeeNumber():?string;
    public function eligibleProperties():array;
    public function findOperationalProperty(int $id):?array;
    public function corporateProperty():?array;
    public function insertAssignment(array $assignment):int;
}
