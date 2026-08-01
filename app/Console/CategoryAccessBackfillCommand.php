<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Services\CategoryAccessBackfillService;
final class CategoryAccessBackfillCommand
{
    public function __construct(private readonly CategoryAccessBackfillService $backfill){}
    public function run(array $arguments):array
    {
        if($arguments!==[])throw new \InvalidArgumentException('This command does not accept arguments.');
        return $this->backfill->run();
    }
}
