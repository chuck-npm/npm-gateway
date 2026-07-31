<?php
declare(strict_types=1);
namespace NpmGateway\Database;
use mysqli;
use NpmGateway\Contracts\PropertyTransactionInterface;
final class MySqlPropertyTransaction implements PropertyTransactionInterface
{
    public function __construct(private readonly mysqli $connection){}
    public function begin():void{$this->connection->begin_transaction();}
    public function commit():void{$this->connection->commit();}
    public function rollback():void{$this->connection->rollback();}
}
