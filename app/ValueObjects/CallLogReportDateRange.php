<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class CallLogReportDateRange
{
 public function __construct(public string$fromDate,public string$toDate,public string$toExclusive,public array$errors,public bool$requested){}
 public function valid():bool{return$this->requested&&$this->errors===[];}
}
