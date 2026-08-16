<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\ValueObjects\ApartmentsReportCriteria;
final readonly class ApartmentsReportCriteriaFactory
{
 public function fromQuery(array$query,array$properties,?array$defaultRange):ApartmentsReportCriteria
 {
  $view=$this->scalar($query['view']??'calls');if(!in_array($view,['calls','email-leads'],true))$view='calls';$requested=array_key_exists('from',$query)||array_key_exists('to',$query);$from=$this->scalar($query['from']??'');$to=$this->scalar($query['to']??'');if(!$requested&&$defaultRange!==null){$from=substr((string)$defaultRange['source_started_at'],0,10);$to=substr((string)$defaultRange['source_ended_at'],0,10);}
  $property=$this->scalar($query['property']??'');$errors=[];if($from!==''&&!$this->date($from))$errors['from']='Enter a valid From Date.';if($to!==''&&!$this->date($to))$errors['to']='Enter a valid To Date.';if(!isset($errors['from'],$errors['to'])&&$from!==''&&$to!==''&&$from>$to)$errors['date_range']='From Date must be on or before To Date.';$allowed=array_column($properties,'public_id');if($property!==''&&!in_array($property,$allowed,true))$errors['property']='Choose an Apartments.com property.';
  $page=filter_var($this->scalar($query['page']??'1'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$per=filter_var($this->scalar($query['per_page']??'100'),FILTER_VALIDATE_INT);if(!in_array($per,[50,100,250],true))$per=100;$exclusive=$to!==''&&!isset($errors['to'])?(new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d'):'';return new ApartmentsReportCriteria($view,$from,$to,$exclusive,$property,$page===false?1:$page,$per,$errors);
 }
 private function scalar(mixed$value):string{return is_scalar($value)?trim((string)$value):'';}
 private function date(string$value):bool{$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);$errors=\DateTimeImmutable::getLastErrors();return$date!==false&&$date->format('Y-m-d')===$value&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0));}
}
