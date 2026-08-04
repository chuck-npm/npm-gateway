<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use mysqli;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class NotificationAcknowledgmentService
{
 public function __construct(private readonly mysqli $db,private readonly NotificationQueryService $queries,private readonly AuditService $audits,private readonly ClockInterface $clock){}
 public function view(string $publicId,AuthenticatedUser $user):?array{$detail=$this->queries->detail($user->id,$publicId);if($detail===null)return null;if($detail['first_viewed_at']===null){$at=$this->clock->now()->format('Y-m-d H:i:s');$s=$this->db->prepare('UPDATE notification_recipients SET first_viewed_at=?,updated_at=? WHERE id=? AND first_viewed_at IS NULL');$s->bind_param('ssi',$at,$at,$detail['recipient_id']);$s->execute();$changed=$s->affected_rows===1;$s->close();if($changed)$this->audits->record('notification.first_viewed',$user->id,$user->employeeId,$user->publicId,'Notification first viewed.',['notification_public_id'=>$publicId,'recipient_user_public_id'=>$user->publicId],$at);$detail['first_viewed_at']=$at;}return $detail;}
 public function acknowledge(string $publicId,AuthenticatedUser $user):?array{$detail=$this->queries->detail($user->id,$publicId);if($detail===null)return null;if($detail['acknowledged_at']!==null)return $detail;$at=$this->clock->now()->format('Y-m-d H:i:s');$this->db->begin_transaction();try{$s=$this->db->prepare('UPDATE notification_recipients SET acknowledged_at=?,updated_at=? WHERE id=? AND acknowledged_at IS NULL');$s->bind_param('ssi',$at,$at,$detail['recipient_id']);$s->execute();$changed=$s->affected_rows===1;$s->close();if($changed)$this->audits->record('notification.acknowledged',$user->id,$user->employeeId,$user->publicId,'Notification acknowledged.',['notification_public_id'=>$publicId,'recipient_user_public_id'=>$user->publicId,'acknowledged_timestamp'=>$at],$at);$this->db->commit();$detail['acknowledged_at']=$at;return $detail;}catch(\Throwable $e){$this->db->rollback();throw $e;}}
}
