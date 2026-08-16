<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class ZillowReportCriteria
{
 public function __construct(public string$type,public string$fromDate,public string$toDate,public string$toExclusive,public string$propertyPublicId,public int$page,public int$perPage,public array$errors){}
 public function valid():bool{return$this->errors===[];}
 public function query(array$overrides=[]):array{return array_merge(['type'=>$this->type,'from'=>$this->fromDate,'to'=>$this->toDate,'property'=>$this->propertyPublicId,'per_page'=>(string)$this->perPage],$overrides);}
}
