<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\ValueObjects\CallLogReportDateRange;
final readonly class CallLogReportDateRangeFactory
{
 public function fromQuery(array$query):CallLogReportDateRange
 {
  $from=$this->scalar($query['from']??'');$to=$this->scalar($query['to']??'');$requested=array_key_exists('from',$query)||array_key_exists('to',$query);$errors=[];
  if($requested&&$from==='')$errors['from']='From Date is required.';elseif($from!==''&&!$this->date($from))$errors['from']='Enter a valid From Date.';
  if($requested&&$to==='')$errors['to']='To Date is required.';elseif($to!==''&&!$this->date($to))$errors['to']='Enter a valid To Date.';
  if(!isset($errors['from'],$errors['to'])&&$from!==''&&$to!==''&&$from>$to)$errors['date_range']='From Date must be on or before To Date.';
  $exclusive=$to!==''&&!isset($errors['to'])?(new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d'):'';
  return new CallLogReportDateRange($from,$to,$exclusive,$errors,$requested);
 }
 private function scalar(mixed$value):string{return is_scalar($value)?trim((string)$value):'';}
 private function date(string$value):bool{$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);$errors=\DateTimeImmutable::getLastErrors();return$date!==false&&$date->format('Y-m-d')===$value&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0));}
}
