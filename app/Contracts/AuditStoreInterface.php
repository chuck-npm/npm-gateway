<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface AuditStoreInterface {
    /** @param array<string, mixed> $event */
    public function insert(array $event): void;
}
