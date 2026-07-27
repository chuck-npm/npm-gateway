<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

final readonly class MigrationStatus
{
    public function __construct(
        public string $migration,
        public string $status,
        public ?int $batch,
        public ?string $executedAt
    ) {
    }
}
