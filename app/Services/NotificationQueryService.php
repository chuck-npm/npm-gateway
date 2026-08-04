<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use mysqli;
use NpmGateway\Repositories\NotificationStorageObjectRepository;
use NpmGateway\ValueObjects\NotificationCount;
final class NotificationQueryService
{
 public function __construct(private readonly mysqli $db,private readonly ?NotificationStorageObjectRepository $assets=null){}
 public function count(int $userId):NotificationCount{$s=$this->db->prepare("SELECT COUNT(*) FROM notification_recipients r JOIN notifications n ON n.id=r.notification_id WHERE r.user_id=? AND r.acknowledged_at IS NULL AND n.status='published' AND n.requires_acknowledgment=1 AND (n.expires_at IS NULL OR n.expires_at>NOW())");$s->bind_param('i',$userId);$s->execute();$count=(int)$s->get_result()->fetch_row()[0];$s->close();return new NotificationCount($count);}
 public function listing(int $userId,string|bool $filter):array{$predicate=$filter==='informational'?"n.requires_acknowledgment=0 AND n.status='published'":($filter===true||$filter==='acknowledged'?"n.requires_acknowledgment=1 AND r.acknowledged_at IS NOT NULL":"n.requires_acknowledgment=1 AND r.acknowledged_at IS NULL AND n.status='published' AND (n.expires_at IS NULL OR n.expires_at>NOW())");$s=$this->db->prepare("SELECT n.public_id,n.title,n.summary,n.priority,n.published_at,n.requires_acknowledgment,n.status,r.first_viewed_at,r.acknowledged_at FROM notification_recipients r JOIN notifications n ON n.id=r.notification_id WHERE r.user_id=? AND {$predicate} ORDER BY n.published_at DESC");$s->bind_param('i',$userId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;}
 public function detail(int $userId,string $publicId):?array{$s=$this->db->prepare("SELECT n.id,n.public_id,n.notification_type,n.title,n.summary,n.payload,n.priority,n.requires_acknowledgment,n.published_at,n.status,r.id recipient_id,r.first_viewed_at,r.acknowledged_at FROM notifications n JOIN notification_recipients r ON r.notification_id=n.id WHERE n.public_id=? AND r.user_id=? LIMIT 1");$s->bind_param('si',$publicId,$userId);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();if(!is_array($row))return null;$row['payload']=json_decode((string)$row['payload'],true,512,JSON_THROW_ON_ERROR);$row['assets']=$row['notification_type']==='company_notice'?$this->assets?->forNotification((int)$row['id'])??[]:[];return $row;}
}
