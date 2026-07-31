<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface PropertyTransactionInterface
{
    public function begin():void;
    public function commit():void;
    public function rollback():void;
}
