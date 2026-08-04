<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
final class NotificationRecipientRepository
{
 public function __construct(private readonly mysqli $db){}
 public function eligibleAudience():array{return $this->db->query("SELECT u.id user_id,u.public_id user_public_id,e.business_email FROM users u JOIN employees e ON e.id=u.employee_id WHERE u.status='active' AND e.employment_status='active' ORDER BY u.id")->fetch_all(MYSQLI_ASSOC);}
 public function insert(int $noticeId,array $r,string $publicId,string $at):void{$email=$this->normalize($r['business_email']??null);$status=$email===null?'skipped_no_email':'pending';$s=$this->db->prepare('INSERT INTO notification_recipients(public_id,notification_id,user_id,business_email_snapshot,assigned_at,email_status,created_at) VALUES(?,?,?,?,?,?,?)');$s->bind_param('siissss',$publicId,$noticeId,$r['user_id'],$email,$at,$status,$at);$s->execute();$s->close();}
 public function groups(int $noticeId):array{$s=$this->db->prepare("SELECT business_email_snapshot,GROUP_CONCAT(id ORDER BY id) recipient_ids FROM notification_recipients WHERE notification_id=? AND email_status='pending' GROUP BY business_email_snapshot");$s->bind_param('i',$noticeId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;}
 public function markEmail(array $ids,bool $sent,string $at):void{if($ids===[])return;$list=implode(',',array_map('intval',$ids));$status=$sent?'sent':'failed';$time=$sent?'email_sent_at':'email_failed_at';$failure=$sent?'NULL':"'transport_failure'";$this->db->query("UPDATE notification_recipients SET email_status='{$status}',{$time}='{$this->db->real_escape_string($at)}',email_failure_code={$failure},updated_at='{$this->db->real_escape_string($at)}' WHERE id IN ({$list})");}
 private function normalize(mixed $email):?string{$value=strtolower(trim((string)$email));return $value!==''&&filter_var($value,FILTER_VALIDATE_EMAIL)!==false?$value:null;}
}
