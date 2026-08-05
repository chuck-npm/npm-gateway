<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Exceptions\Domain\InvalidEmergencyContactException;
use NpmGateway\Repositories\EmployeeEmergencyContactRepository;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\EmployeeEmergencyContact;
final class EmployeeEmergencyContactService
{
 public function __construct(private readonly EmployeeEmergencyContactRepository $contacts,private readonly InitializationTransactionInterface $tx,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly PhoneFormatter $phones,private readonly AuditService $audits){}
 public function findFor(AuthenticatedUser $user):?EmployeeEmergencyContact{if($user->employeeId<1||$user->employeePublicId==='')throw new \DomainException('Account context is unavailable.');return $this->contacts->findByEmployeeId($user->employeeId);}
 public function save(AuthenticatedUser $user,array $submitted):EmployeeEmergencyContact
 {
  if($user->employeeId<1||$user->employeePublicId==='')throw new \DomainException('Account context is unavailable.');$data=$this->validate($submitted);$at=$this->clock->now()->format('Y-m-d H:i:s');$this->tx->begin();try{$created=!$this->contacts->existsForEmployee($user->employeeId);if($created)$this->contacts->insert($user->employeeId,$this->ids->generate(),$data,$at);else $this->contacts->update($user->employeeId,$data,$at);$this->audits->record($created?'employee.emergency_contact_created':'employee.emergency_contact_updated',$user->id,$user->employeeId,$user->publicId,'Employee emergency contact information saved.',['employee_public_id'=>$user->employeePublicId,'operation'=>$created?'created':'updated','alternate_phone_present'=>$data['alternate_phone']!==null],$at);$this->tx->commit();return $this->contacts->findByEmployeeId($user->employeeId)??throw new \RuntimeException('Emergency contact save result unavailable.');}catch(\Throwable $e){$this->tx->rollback();throw $e;}
 }
 public function saveForMaintenanceByManager(AuthenticatedUser $actor,array $employee,array $submitted):EmployeeEmergencyContact{$data=$this->validate($submitted);$at=$this->clock->now()->format('Y-m-d H:i:s');$employeeId=(int)$employee['id'];$this->tx->begin();try{$created=!$this->contacts->existsForEmployee($employeeId);if($created)$this->contacts->insert($employeeId,$this->ids->generate(),$data,$at);else $this->contacts->update($employeeId,$data,$at);$this->audits->recordEmployee($created?'employee.emergency_contact_created_by_manager':'employee.emergency_contact_updated_by_manager',$actor->id,$actor->employeeId,$employeeId,(string)$employee['public_id'],'Manager saved Maintenance employee emergency contact information.',['target_employee_public_id'=>$employee['public_id'],'acting_manager_public_id'=>$actor->employeePublicId,'property_public_id'=>$employee['property_public_id'],'operation'=>$created?'created':'updated','alternate_phone_present'=>$data['alternate_phone']!==null],$at);$this->tx->commit();return $this->contacts->findByEmployeeId($employeeId)??throw new \RuntimeException('Emergency contact save result unavailable.');}catch(\Throwable $e){$this->tx->rollback();throw $e;}}
 public function recordHrView(AuthenticatedUser $actor,array $employee):void{$at=$this->clock->now()->format('Y-m-d H:i:s');$this->audits->recordEmployee('employee.emergency_contact_viewed_by_hr',$actor->id,$actor->employeeId,(int)$employee['id'],(string)$employee['public_id'],'HR viewed employee emergency contact information.',['target_employee_public_id'=>$employee['public_id'],'acting_user_public_id'=>$actor->publicId,'operation'=>'viewed','employee_class'=>$employee['employee_class']],$at);}
 public function validate(array $submitted):array
 {
  $errors=[];$data=[];foreach(['first_name'=>'First Name','last_name'=>'Last Name','relationship'=>'Relationship'] as $field=>$label){$value=$submitted[$field]??null;if(!is_string($value)){$errors[$field]="{$label} is required.";continue;}$value=trim(preg_replace('/\s+/u',' ',$value)??'');if($value===''||mb_strlen($value)>100||preg_match('/[\x00-\x1F\x7F<>]/u',$value)){$errors[$field]="Enter a valid {$label}.";continue;}$data[$field]=$value;}
  foreach(['primary_phone'=>'Primary Phone','alternate_phone'=>'Alternate Phone'] as $field=>$label){$value=$submitted[$field]??'';if(!is_string($value)){$errors[$field]="Enter a valid {$label}.";continue;}$value=trim($value);if($field==='alternate_phone'&&$value===''){$data[$field]=null;continue;}$normalized=$this->phones->normalize($value);if($normalized===null){$errors[$field]=$field==='primary_phone'?'Primary Phone is required and must contain a valid 10-digit phone number.':'Enter a valid Alternate Phone.';}else $data[$field]=$normalized;}
  $safe=[];foreach(['first_name','last_name','relationship','primary_phone','alternate_phone'] as $field)if(is_string($submitted[$field]??null))$safe[$field]=(string)$submitted[$field];if($errors!==[])throw new InvalidEmergencyContactException($errors,$safe);return $data;
 }
}
