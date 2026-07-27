<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface InitializationTransactionInterface
{
    public function acquire(string $lockName, int $timeoutSeconds): bool;
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
    public function release(string $lockName): void;
}
