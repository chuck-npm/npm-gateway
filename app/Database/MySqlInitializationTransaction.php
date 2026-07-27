<?php
declare(strict_types=1);
namespace NpmGateway\Database;
use mysqli;
use NpmGateway\Contracts\InitializationTransactionInterface;
final class MySqlInitializationTransaction implements InitializationTransactionInterface
{
    public function __construct(private readonly mysqli $connection) {}
    public function acquire(string $lockName, int $timeoutSeconds): bool
    {
        $statement = $this->connection->prepare('SELECT GET_LOCK(?, ?)');
        $statement->bind_param('si', $lockName, $timeoutSeconds);
        $statement->execute();
        $acquired = (int) ($statement->get_result()->fetch_row()[0] ?? 0) === 1;
        $statement->close();
        return $acquired;
    }
    public function begin(): void { $this->connection->begin_transaction(); }
    public function commit(): void { $this->connection->commit(); }
    public function rollback(): void { $this->connection->rollback(); }
    public function release(string $lockName): void
    {
        $statement = $this->connection->prepare('SELECT RELEASE_LOCK(?)');
        $statement->bind_param('s', $lockName);
        $statement->execute();
        $statement->close();
    }
}
