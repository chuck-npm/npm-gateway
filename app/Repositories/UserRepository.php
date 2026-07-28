<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\UserStoreInterface;
final class UserRepository implements UserStoreInterface
{
    public function __construct(private readonly mysqli $connection) {}
    public function anyExists(): bool
    {
        $result = $this->connection->query('SELECT 1 FROM users LIMIT 1');
        $exists = $result->num_rows > 0;
        $result->free();
        return $exists;
    }
    public function usernameExists(string $username): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
        $statement->bind_param('s', $username);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
    public function insert(array $user): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users
             (public_id, employee_id, username, password_hash, status, password_changed_at,
              password_reset_at, failed_login_count, locked_until)
             VALUES (?, ?, ?, ?, ?, ?, NULL, 0, NULL)'
        );
        $statement->bind_param(
            'sissss',
            $user['public_id'], $user['employee_id'], $user['username'],
            $user['password_hash'], $user['status'], $user['password_changed_at']
        );
        $statement->execute();
        $id = $this->connection->insert_id;
        $statement->close();
        return $id;
    }
    /** @return array<string,mixed>|null */
    public function findForAuthentication(string $username): ?array
    {
        $statement=$this->connection->prepare('SELECT u.id,u.public_id,u.employee_id,u.username,u.password_hash,u.status,u.failed_login_count,u.locked_until,e.public_id employee_public_id,e.first_name,e.last_name,e.job_title,e.employee_class,e.employment_status FROM users u JOIN employees e ON e.id=u.employee_id WHERE u.username=? LIMIT 1');
        $statement->bind_param('s',$username);$statement->execute();$row=$statement->get_result()->fetch_assoc();$statement->close();
        return is_array($row)?$row:null;
    }
    public function recordFailure(int $id,int $count,?string $lockedUntil):void
    {
        $statement=$this->connection->prepare('UPDATE users SET failed_login_count=?,locked_until=? WHERE id=?');
        $statement->bind_param('isi',$count,$lockedUntil,$id);$statement->execute();$statement->close();
    }
    public function recordSuccess(int $id,string $at):void
    {
        $statement=$this->connection->prepare('UPDATE users SET failed_login_count=0,locked_until=NULL,last_login_at=? WHERE id=?');
        $statement->bind_param('si',$at,$id);$statement->execute();$statement->close();
    }
    public function updatePasswordHash(int $id,string $hash):void
    {
        $statement=$this->connection->prepare('UPDATE users SET password_hash=? WHERE id=?');$statement->bind_param('si',$hash,$id);$statement->execute();$statement->close();
    }
    /** @return array<string,mixed>|null */
    public function findActiveIdentity(int $id):?array
    {
        $statement=$this->connection->prepare("SELECT u.id,u.public_id,u.employee_id,u.username,u.status,e.public_id employee_public_id,e.first_name,e.last_name,e.job_title,e.employee_class,e.employment_status FROM users u JOIN employees e ON e.id=u.employee_id WHERE u.id=? LIMIT 1");
        $statement->bind_param('i',$id);$statement->execute();$row=$statement->get_result()->fetch_assoc();$statement->close();return is_array($row)?$row:null;
    }
}
