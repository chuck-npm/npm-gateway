<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\ValueObjects\CallLogFilterCriteria;
final readonly class CallLogFilterCriteriaFactory
{
 public function fromQuery(array$query,array$destinations):CallLogFilterCriteria
 {
  $errors=[];$from=$this->scalar($query['from']??'');$to=$this->scalar($query['to']??'');$property=$this->scalar($query['property']??'');if($from!==''&&!$this->date($from))$errors['from']='Enter a valid From Date.';if($to!==''&&!$this->date($to))$errors['to']='Enter a valid To Date.';if(!isset($errors['from'],$errors['to'])&&$from!==''&&$to!==''&&$from>$to)$errors['date_range']='From Date must be on or before To Date.';$allowed=array_column($destinations,'public_id');if($property!==''&&!in_array($property,$allowed,true))$errors['property']='Choose a configured Call Log property.';$per=filter_var($this->scalar($query['per_page']??'500'),FILTER_VALIDATE_INT);if(!in_array($per,[100,250,500],true))$per=500;$page=filter_var($this->scalar($query['page']??'1'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$exclusive=$to!==''&&!isset($errors['to'])?(new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d'):'';return new CallLogFilterCriteria($from,$to,$exclusive,$property,$page===false?1:$page,$per,$errors);
 }
 private function scalar(mixed$value):string{return is_scalar($value)?trim((string)$value):'';}
 private function date(string$value):bool{$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);$errors=\DateTimeImmutable::getLastErrors();return$date!==false&&$date->format('Y-m-d')===$value&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0));}
}
