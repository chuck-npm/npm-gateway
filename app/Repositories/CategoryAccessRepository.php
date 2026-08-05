<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
final class CategoryAccessRepository implements CategoryAccessStoreInterface
{
    public function __construct(private readonly mysqli $connection){}
    public function hasEffectiveMembership(int $userId,string $category):bool{$s=$this->connection->prepare("SELECT 1 FROM user_category_access a JOIN users u ON u.id=a.user_id WHERE a.user_id=? AND a.category=? AND u.status='active' LIMIT 1");$s->bind_param('is',$userId,$category);$s->execute();$found=$s->get_result()->num_rows===1;$s->close();return $found;}
    public function findUserByUsername(string $username):?array{$s=$this->connection->prepare('SELECT u.id,u.public_id,u.employee_id,u.username,u.status FROM users u WHERE u.username=? LIMIT 2');$s->bind_param('s',$username);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return count($rows)===1?$rows[0]:null;}
    public function allUsers():array{return $this->connection->query("SELECT u.id,u.public_id,u.employee_id,u.username,u.status,e.public_id employee_public_id,e.employment_status,CONCAT(e.first_name,' ',e.last_name) display_name FROM users u JOIN employees e ON e.id=u.employee_id ORDER BY e.last_name,e.first_name,u.username")->fetch_all(MYSQLI_ASSOC);}
    public function memberships():array{return $this->connection->query('SELECT user_id,category FROM user_category_access ORDER BY user_id,category')->fetch_all(MYSQLI_ASSOC);}
    public function grant(array $m):void{$s=$this->connection->prepare('INSERT INTO user_category_access(public_id,user_id,category,granted_by_user_id,granted_at,updated_by_user_id,updated_at) VALUES(?,?,?,?,?,NULL,NULL)');$s->bind_param('sisis',$m['public_id'],$m['user_id'],$m['category'],$m['granted_by_user_id'],$m['granted_at']);$s->execute();$s->close();}
    public function revoke(int $userId,string $category):void{$s=$this->connection->prepare('DELETE FROM user_category_access WHERE user_id=? AND category=?');$s->bind_param('is',$userId,$category);$s->execute();$s->close();}
}
