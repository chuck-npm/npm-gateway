<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
final class NotificationStorageObjectRepository
{
 public function __construct(private readonly \mysqli $db){}
 public function insert(int $notificationId,int $storageId,string $publicId,string $role,int $sort,int $actorId):void{$s=$this->db->prepare('INSERT INTO notification_storage_objects(public_id,notification_id,storage_object_id,asset_role,sort_order,linked_by_user_id) VALUES (?,?,?,?,?,?)');$s->bind_param('siisii',$publicId,$notificationId,$storageId,$role,$sort,$actorId);$s->execute();$s->close();}
 public function forNotification(int $notificationId,?string $role=null):array{if($role===null){$s=$this->db->prepare('SELECT s.public_id,s.display_filename,s.mime_type,s.byte_size,n.asset_role,n.sort_order FROM notification_storage_objects n JOIN storage_objects s ON s.id=n.storage_object_id WHERE n.notification_id=? ORDER BY n.sort_order,n.id');$s->bind_param('i',$notificationId);}else{$s=$this->db->prepare('SELECT s.public_id,s.display_filename,s.mime_type,s.byte_size,n.asset_role,n.sort_order FROM notification_storage_objects n JOIN storage_objects s ON s.id=n.storage_object_id WHERE n.notification_id=? AND n.asset_role=? ORDER BY n.sort_order,n.id');$s->bind_param('is',$notificationId,$role);}$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;}
}
