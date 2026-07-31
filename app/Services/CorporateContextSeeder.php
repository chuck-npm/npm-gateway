<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\PropertyTransactionInterface;
use NpmGateway\ValueObjects\CorporateContextResult;
final class CorporateContextSeeder
{
    public function __construct(private readonly PropertyTransactionInterface $transaction,private readonly CorporateContextService $contexts){}
    public function seed(string $source='local-backfill'):CorporateContextResult{$this->transaction->begin();try{$result=$this->contexts->ensure($source);$this->transaction->commit();return $result;}catch(\Throwable $e){$this->transaction->rollback();throw $e;}}
}
