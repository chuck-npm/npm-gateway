<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use mysqli;
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Support\PublicIdGenerator;
final class ProtectedPrincipalMaintenanceService
{
    public function __construct(private readonly mysqli $db,private readonly ProtectedPrincipalConfig $config,private readonly AuditService $audits,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock){}
    public function proposedRepairs(bool $lock=false):array
    {
        if(!$this->config->configured())throw new \RuntimeException('Protected principal is not configured.');
        $sql='SELECT u.id,u.public_id,u.employee_id,u.username,u.status,u.disabled_at,e.id employee_id_actual,e.public_id employee_public_id,e.employment_status FROM users u JOIN employees e ON e.id=u.employee_id WHERE u.public_id=? LIMIT 2'.($lock?' FOR UPDATE':'');
        $s=$this->db->prepare($sql);$userPublicId=$this->config->userPublicId;$s->bind_param('s',$userPublicId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        if(count($rows)!==1)throw new \RuntimeException('Protected identity is missing or ambiguous; manual database-administrator review is required.');$row=$rows[0];
        if($row['employee_public_id']!==$this->config->employeePublicId)throw new \RuntimeException('Protected user/employee linkage conflicts with configuration; manual database-administrator review is required.');
        if(preg_match('/^[a-z][a-z0-9]{1,49}$/',(string)$row['username'])!==1)throw new \RuntimeException('Protected username is invalid; manual database-administrator review is required.');
        $repairs=[];if($row['status']!=='active')$repairs[]='reactivate_user';if($row['employment_status']!=='active')$repairs[]='reactivate_employee';$s=$this->db->prepare('SELECT category FROM user_category_access WHERE user_id=?');$userId=(int)$row['id'];$s->bind_param('i',$userId);$s->execute();$current=array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'category');$s->close();foreach($this->config->requiredCategories as $category)if(!in_array($category,$current,true))$repairs[]='grant_category:'.$category;return ['identity'=>$row,'repairs'=>$repairs];
    }
    public function repair():array
    {
        $this->db->begin_transaction();try{$proposal=$this->proposedRepairs(true);$row=$proposal['identity'];$at=$this->clock->now()->format('Y-m-d H:i:s');$userId=(int)$row['id'];$employeeId=(int)$row['employee_id_actual'];$userPublic=(string)$row['public_id'];$employeePublic=(string)$row['employee_public_id'];foreach($proposal['repairs'] as $repair){if($repair==='reactivate_user'){$s=$this->db->prepare("UPDATE users SET status='active',disabled_at=NULL,updated_at=? WHERE id=? AND public_id=?");$s->bind_param('sis',$at,$userId,$userPublic);$s->execute();$s->close();}elseif($repair==='reactivate_employee'){$s=$this->db->prepare("UPDATE employees SET employment_status='active',termination_date=NULL,updated_at=? WHERE id=? AND public_id=?");$s->bind_param('sis',$at,$employeeId,$employeePublic);$s->execute();$s->close();}elseif(str_starts_with($repair,'grant_category:')){$category=substr($repair,15);$public=$this->ids->generate();$s=$this->db->prepare('INSERT INTO user_category_access(public_id,user_id,category,granted_by_user_id,granted_at) VALUES(?,?,?,?,?)');$s->bind_param('sisis',$public,$userId,$category,$userId,$at);$s->execute();$s->close();}}if($proposal['repairs']!==[])$this->audits->recordSystem('security.protected_principal_repaired','user',$userId,$userPublic,'Protected principal baseline repaired.',['target_user_public_id'=>$userPublic,'repairs'=>$proposal['repairs'],'denial_reason'=>'approved_cli_repair'],$at);$this->db->commit();return $proposal;}catch(\Throwable $e){$this->db->rollback();throw $e;}
    }
}
