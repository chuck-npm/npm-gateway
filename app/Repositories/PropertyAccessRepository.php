<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\PropertyAccessStoreInterface;
final class PropertyAccessRepository implements PropertyAccessStoreInterface
{
 public function __construct(private readonly mysqli $db){}
 public function hasEffectiveAccess(int $userId,int $propertyId):bool{$s=$this->db->prepare("SELECT 1 FROM user_property_access a JOIN users u ON u.id=a.user_id JOIN properties p ON p.id=a.property_id WHERE a.user_id=? AND a.property_id=? AND u.status='active' AND p.status='active' LIMIT 1");$s->bind_param('ii',$userId,$propertyId);$s->execute();$found=$s->get_result()->num_rows===1;$s->close();return $found;}
 public function accessibleActiveProperties(int $userId):array{$s=$this->db->prepare("SELECT p.id,p.public_id,p.slug,p.property_code,p.display_name FROM user_property_access a JOIN users u ON u.id=a.user_id JOIN properties p ON p.id=a.property_id WHERE a.user_id=? AND u.status='active' AND p.status='active' ORDER BY p.display_name,p.id");$s->bind_param('i',$userId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;}
 public function activePropertyBySlug(string $slug):?array{$s=$this->db->prepare("SELECT id,public_id,slug,property_code,display_name FROM properties WHERE slug=? AND status='active' LIMIT 2");$s->bind_param('s',$slug);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return count($rows)===1?$rows[0]:null;}
 public function employeesForMatrix():array{return $this->db->query("SELECT e.id employee_id,e.public_id employee_public_id,CONCAT(e.first_name,' ',e.last_name) display_name,e.employee_class,e.employment_status,u.id user_id,u.public_id user_public_id,u.status user_status FROM employees e LEFT JOIN users u ON u.employee_id=e.id ORDER BY e.last_name,e.first_name,e.id")->fetch_all(MYSQLI_ASSOC);}
 public function activeProperties():array{return $this->db->query("SELECT id,public_id,property_code,display_name,slug FROM properties WHERE status='active' ORDER BY display_name,id")->fetch_all(MYSQLI_ASSOC);}
 public function memberships():array{return $this->db->query('SELECT user_id,property_id FROM user_property_access ORDER BY user_id,property_id')->fetch_all(MYSQLI_ASSOC);}
 public function grant(array $g):void{$s=$this->db->prepare('INSERT IGNORE INTO user_property_access(public_id,user_id,property_id,granted_by_user_id,granted_at,updated_by_user_id,updated_at) VALUES(?,?,?,?,?,NULL,NULL)');$s->bind_param('siiis',$g['public_id'],$g['user_id'],$g['property_id'],$g['granted_by_user_id'],$g['granted_at']);$s->execute();$s->close();}
 public function revoke(int $userId,int $propertyId):void{$s=$this->db->prepare('DELETE FROM user_property_access WHERE user_id=? AND property_id=?');$s->bind_param('ii',$userId,$propertyId);$s->execute();$s->close();}
}
