<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\HrEmployeeStoreInterface;
use NpmGateway\Contracts\NotificationPublisherInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\Exceptions\Domain\InvalidHrEmployeeDataException;
use NpmGateway\Exceptions\Domain\ActivePrimaryPropertyManagerConflictException;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\HrEmployeeCreationResult;
final class HrEmployeeCreationService
{
    private const LOCK='npm_gateway:employee_number_allocation';
    public function __construct(private readonly HrEmployeeValidator $validator,private readonly HrEmployeeStoreInterface $employees,private readonly InitializationTransactionInterface $transaction,private readonly UserService $users,private readonly PasswordService $passwords,private readonly AuditService $audits,private readonly HrEmployeeNotificationService $notifications,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly array $notificationConfig,private readonly ?EmployeeAnnouncementFactory $announcementFactory=null,private readonly ?NotificationPublisherInterface $publisher=null,private readonly ?CompanyAnnouncementDispatchService $dispatcher=null){}
    public function eligibleProperties():array{return $this->employees->eligibleProperties();}
    public function currentDate():string{return $this->clock->now()->format('Y-m-d');}
    public function create(array $input,AuthenticatedUser $actor,?\Closure $onCommitted=null):HrEmployeeCreationResult
    {
        $data=$this->validator->validate($input);
        try{$this->notifications->validateConfiguration();}catch(CredentialNotificationException){throw new InvalidHrEmployeeDataException(['notification'=>'Secure notification configuration is unavailable; no employee was created.'],$data);}
        if(!$this->transaction->acquire(self::LOCK,5))throw new InvalidHrEmployeeDataException(['employee_number'=>'Employee-number allocation is busy. Try again.'],$data);
        $credential=null;$employee=[];$user=[];$timestamp='';
        try{
            $this->transaction->begin();
            try{
                $highest=$this->employees->highestEmployeeNumber();$next=$highest===null?1:(int)substr($highest,3)+1;
                if($next>999999)throw new InvalidHrEmployeeDataException(['employee_number'=>'Employee-number capacity has been reached.'],$data);
                $number='NPM'.str_pad((string)$next,6,'0',STR_PAD_LEFT);
                if($this->employees->employeeNumberExists($number))throw new \RuntimeException('Employee number allocation conflict.');
                $timestamp=$this->clock->now()->format('Y-m-d H:i:s');$employeePublic=$this->ids->generate();
                $employeeId=$this->employees->insert(['public_id'=>$employeePublic,'employee_number'=>$number,'employee_class'=>$data['employee_class'],'first_name'=>$data['first_name'],'last_name'=>$data['last_name'],'date_of_birth'=>$data['date_of_birth'],'business_email'=>$data['business_email'],'personal_email'=>$data['personal_email'],'company_phone'=>$data['company_phone'],'personal_phone'=>$data['personal_phone'],'job_title'=>$data['job_title'],'employment_status'=>$data['employment_status'],'start_date'=>$data['start_date'],'comments'=>$data['comments'],'created_by'=>$actor->id,'updated_by'=>$actor->id]);
                if($data['employee_type']!=='corporate')$this->employees->insertAssignment(['public_id'=>$this->ids->generate(),'employee_id'=>$employeeId,'property_id'=>(int)$data['property']['id'],'assignment_type'=>$data['assignment_type'],'starts_on'=>$data['start_date'],'created_by'=>$actor->id,'updated_by'=>$actor->id]);
                $credential=$this->passwords->generate();$user=$this->users->createBootstrapUser($employeeId,$data['username'],$credential->passwordHash,$timestamp);$employee=['id'=>$employeeId,'public_id'=>$employeePublic,'employee_number'=>$number];
                $safe=['employee_public_id'=>$employeePublic,'employee_number'=>$number,'employee_class'=>$data['employee_class'],'job_title'=>$data['job_title'],'status'=>$data['employment_status'],'start_date'=>$data['start_date'],'user_public_id'=>$user['public_id'],'username'=>$user['username'],'creator_public_id'=>$actor->publicId];
                if($data['employee_type']!=='corporate')$safe+=['property_public_id'=>$data['property']['public_id'],'property_code'=>$data['property']['property_code'],'assignment_type'=>$data['assignment_type']];
                $this->audits->record('hr.employee_created',$actor->id,$employeeId,$employeePublic,'HR employee, assignment, and Gateway account created.',$safe,$timestamp);$this->transaction->commit();if($onCommitted!==null)$onCommitted($employeePublic);
            }catch(\Throwable $e){$this->transaction->rollback();if($e instanceof ActivePrimaryPropertyManagerConflictException)throw new InvalidHrEmployeeDataException(['property_id'=>'This property already has an active primary Property Manager.'],$data);throw $e;}
        }finally{$this->transaction->release(self::LOCK);}
        $notice=$data+['employee_number'=>$employee['employee_number'],'full_name'=>$data['first_name'].' '.$data['last_name'],'employee_type_label'=>ucwords(str_replace('_',' ',$data['employee_type'])),'context_name'=>$data['employee_type']==='corporate'?'Corporate':$data['property']['display_name'],'property_code'=>$data['property']['property_code'],'prop_id'=>(string)$data['property']['prop_id'],'primary_assignment'=>'Yes','created_by_name'=>$actor->displayName,'created_by_username'=>$actor->username,'created_at'=>$timestamp,'environment'=>(string)($this->notificationConfig['environment']??'production')];
        try{$this->notifications->send($notice,$credential->plaintextPassword());$this->audits->record('hr.employee_notification_sent',$actor->id,(int)$employee['id'],(string)$employee['public_id'],'Secure new-employee notification sent.',['employee_public_id'=>$employee['public_id'],'employee_number'=>$employee['employee_number'],'username'=>$user['username'],'notification_status'=>'sent','creator_public_id'=>$actor->publicId],$timestamp);}catch(CredentialNotificationException){$this->audits->record('hr.employee_notification_failed',$actor->id,(int)$employee['id'],(string)$employee['public_id'],'Secure new-employee notification failed.',['employee_public_id'=>$employee['public_id'],'employee_number'=>$employee['employee_number'],'username'=>$user['username'],'notification_status'=>'failed','creator_public_id'=>$actor->publicId],$timestamp);return new HrEmployeeCreationResult(false);}
        if($this->announcementFactory===null||$this->publisher===null||$this->dispatcher===null)return new HrEmployeeCreationResult(true);
        try{$announcement=$this->announcementFactory->create((string)$employee['public_id'],$notice);$published=$this->publisher->publish($announcement,$actor->id,$actor->employeeId,$actor->publicId);if(($published['already_published']??false)===true)return new HrEmployeeCreationResult(true,true);$delivery=$this->dispatcher->dispatch((int)$published['id'],(string)$published['public_id'],$announcement,$actor);return new HrEmployeeCreationResult(true,true,(int)$delivery['failed']);}catch(\Throwable){$this->audits->record('notification.publication_failed',$actor->id,(int)$employee['id'],(string)$employee['public_id'],'Company notification publication failed.',['notification_type'=>'employee_created','source_entity_public_id'=>$employee['public_id'],'creator_public_id'=>$actor->publicId],$this->clock->now()->format('Y-m-d H:i:s'));return new HrEmployeeCreationResult(true,false);}
    }
}
