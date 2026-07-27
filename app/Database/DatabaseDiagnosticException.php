<?php

declare(strict_types=1);

namespace NpmGateway\Database;

use RuntimeException;

final class DatabaseDiagnosticException extends RuntimeException
{
    /**
     * @param array<string, string> $report
     */
    public function __construct(string $message, private readonly array $report)
    {
        parent::__construct($message);
    }

    /**
     * @return array<string, string>
     */
    public function report(): array
    {
        return $this->report;
    }
}
