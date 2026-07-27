<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface UserStoreInterface {
    public function anyExists(): bool;
    public function usernameExists(string $username): bool;
    /** @param array<string, mixed> $user */
    public function insert(array $user): int;
}
