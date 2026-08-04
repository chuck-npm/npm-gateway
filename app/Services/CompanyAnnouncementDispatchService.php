<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\CompanyAnnouncementEmailSenderInterface;
use NpmGateway\Repositories\NotificationRecipientRepository;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
final class CompanyAnnouncementDispatchService
{
 public function __construct(private readonly NotificationRecipientRepository $recipients,private readonly CompanyAnnouncementEmailSenderInterface $sender,private readonly ClockInterface $clock,private readonly AuditService $audits){}
 public function dispatch(int $noticeId,string $noticePublicId,EmployeeAnnouncement $a,AuthenticatedUser $actor):array{$sent=0;$failed=0;$at=$this->clock->now()->format('Y-m-d H:i:s');foreach($this->recipients->groups($noticeId) as $group){$ok=$this->sender->send((string)$group['business_email_snapshot'],$a);$ids=array_map('intval',explode(',',(string)$group['recipient_ids']));$this->recipients->markEmail($ids,$ok,$at);$ok?$sent++:$failed++;}$event=$failed===0?'notification.email_dispatch_completed':'notification.email_dispatch_failed';$this->audits->record($event,$actor->id,$actor->employeeId,$actor->publicId,'Company announcement email dispatch finished.',['notification_public_id'=>$noticePublicId,'email_sent_count'=>$sent,'email_failed_count'=>$failed],$at);return ['sent'=>$sent,'failed'=>$failed];}
}
