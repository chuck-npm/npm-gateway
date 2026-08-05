<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Domain\EmployeeClass;
final class NotificationRecipientRepository
{
 public function __construct(private readonly mysqli $db){}
 public function eligibleAudienceCandidates():array{$classes=EmployeeClass::NOTIFICATION_ELIGIBLE;$s=$this->db->prepare("SELECT u.id user_id,u.public_id user_public_id,u.status user_status,e.id employee_id,e.public_id employee_public_id,e.employee_class,e.employment_status,e.business_email FROM users u JOIN employees e ON e.id=u.employee_id WHERE u.status='active' AND e.employment_status='active' AND e.employee_class IN (?,?) ORDER BY u.id");$s->bind_param('ss',$classes[0],$classes[1]);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;}
 public function targetedCandidate(string $userPublicId):?array{$s=$this->db->prepare('SELECT u.id user_id,u.public_id user_public_id,u.status user_status,e.id employee_id,e.public_id employee_public_id,e.employee_class,e.employment_status,e.business_email FROM users u JOIN employees e ON e.id=u.employee_id WHERE u.public_id=? LIMIT 1');$s->bind_param('s',$userPublicId);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();return is_array($row)?$row:null;}
 public function insert(int $noticeId,array $r,string $publicId,string $at):void{$email=$this->normalize($r['business_email']??null);$status=$email===null?'skipped_no_email':'pending';$s=$this->db->prepare('INSERT INTO notification_recipients(public_id,notification_id,user_id,business_email_snapshot,assigned_at,email_status,created_at) VALUES(?,?,?,?,?,?,?)');$s->bind_param('siissss',$publicId,$noticeId,$r['user_id'],$email,$at,$status,$at);$s->execute();$s->close();}
 public function groups(int $noticeId):array{$s=$this->db->prepare("SELECT business_email_snapshot,GROUP_CONCAT(id ORDER BY id) recipient_ids FROM notification_recipients WHERE notification_id=? AND email_status='pending' GROUP BY business_email_snapshot");$s->bind_param('i',$noticeId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;}
 public function markEmail(array $ids,bool $sent,string $at):void{if($ids===[])return;$list=implode(',',array_map('intval',$ids));$status=$sent?'sent':'failed';$time=$sent?'email_sent_at':'email_failed_at';$failure=$sent?'NULL':"'transport_failure'";$this->db->query("UPDATE notification_recipients SET email_status='{$status}',{$time}='{$this->db->real_escape_string($at)}',email_failure_code={$failure},updated_at='{$this->db->real_escape_string($at)}' WHERE id IN ({$list})");}
 private function normalize(mixed $email):?string{$value=strtolower(trim((string)$email));return $value!==''&&filter_var($value,FILTER_VALIDATE_EMAIL)!==false?$value:null;}
}
