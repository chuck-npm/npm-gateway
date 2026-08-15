<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class CallLogFilterCriteria
{
 public function __construct(public string$fromDate,public string$toDate,public string$toExclusive,public string$destinationPublicId,public int$page,public int$perPage,public array$errors){}
 public function active():bool{return$this->fromDate!==''||$this->toDate!==''||$this->destinationPublicId!=='';}
}
