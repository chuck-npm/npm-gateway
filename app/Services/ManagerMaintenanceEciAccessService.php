<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\EmployeeEmergencyContactRepository;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\EmployeeEmergencyContact;
final class ManagerMaintenanceEciAccessService
{
 public function __construct(private readonly EmployeeEmergencyContactRepository $contacts){}
 public function authorizedTarget(AuthenticatedUser $manager,string $targetPublicId):?array{if($manager->employeeClass!=='manager'||$manager->employeeId<1)return null;return $this->contacts->findAuthorizedMaintenanceForManager($manager->employeeId,$targetPublicId);}
 public function contactFor(array $employee):?EmployeeEmergencyContact{return $this->contacts->findByEmployeeId((int)$employee['id']);}
}
