<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

use mysqli;

interface MigrationInterface
{
    public function up(mysqli $connection): void;

    public function down(mysqli $connection): void;
}
