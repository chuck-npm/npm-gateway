<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class CallLogsSchema
{
    public const MIGRATION='202608140024_call_logs';
    public const TABLES=['call_log_destinations','call_log_imports','call_logs'];
}
