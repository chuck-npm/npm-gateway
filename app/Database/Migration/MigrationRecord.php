<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

final readonly class MigrationRecord
{
    public function __construct(
        public string $migration,
        public int $batch,
        public string $executedAt
    ) {
    }
}
