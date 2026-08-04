<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Services\OperationsAccessBackfillService;
final class OperationsAccessBackfillCommand
{
 public function __construct(private readonly OperationsAccessBackfillService $backfill){}
 public function run(array $arguments):array{if($arguments!==[])throw new \InvalidArgumentException('This command does not accept arguments.');return $this->backfill->run();}
}
