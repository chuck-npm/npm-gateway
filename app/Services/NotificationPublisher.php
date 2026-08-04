<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Contracts\NotificationPublisherInterface;
use NpmGateway\Repositories\NotificationRepository;
use NpmGateway\Repositories\NotificationRecipientRepository;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
final class NotificationPublisher implements NotificationPublisherInterface
{
 public function __construct(private readonly NotificationRepository $notices,private readonly NotificationRecipientRepository $recipients,private readonly InitializationTransactionInterface $tx,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly AuditService $audits){}
 public function publish(EmployeeAnnouncement $a,int $actorUserId,int $actorEmployeeId,string $actorPublicId):array
 {
  if(array_keys($a->payload)!==['employee_name','job_title','start_date','company_phone','business_email','primary_property'])throw new \InvalidArgumentException('Invalid employee announcement payload.');
  if($existing=$this->notices->findBySource('employee_created',$a->sourcePublicId))return $existing+['already_published'=>true];
  $at=$this->clock->now()->format('Y-m-d H:i:s');$audience=$this->recipients->eligibleAudience();$this->tx->begin();
  try{$public=$this->ids->generate();$id=$this->notices->insert(['public_id'=>$public,'title'=>$a->title,'summary'=>$a->summary,'payload'=>$a->payload,'source_public_id'=>$a->sourcePublicId,'actor_id'=>$actorUserId,'timestamp'=>$at]);foreach($audience as $r)$this->recipients->insert($id,$r,$this->ids->generate(),$at);$this->audits->record('notification.published',$actorUserId,$actorEmployeeId,$actorPublicId,'Company notification published.',['notification_public_id'=>$public,'notification_type'=>'employee_created','source_entity_public_id'=>$a->sourcePublicId,'published_recipient_count'=>count($audience)],$at);$this->tx->commit();return ['id'=>$id,'public_id'=>$public,'already_published'=>false];}catch(\Throwable $e){$this->tx->rollback();throw $e;}
 }
}
