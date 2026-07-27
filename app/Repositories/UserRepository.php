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
}
