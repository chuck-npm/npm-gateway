<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class RmAuditOverviewCriteria
{
 public function __construct(public string $from,public string $to,public string $propertyPublicId,public string $status,public string $startBoundary,public string $endBoundary,public array $errors=[]){ }
 public static function fromQuery(array $query,\DateTimeImmutable $now,array $properties):self
 {
  $today=$now->format('Y-m-d');$defaults=!array_key_exists('from',$query)&&!array_key_exists('to',$query);$from=$defaults?$now->format('Y-m-01'):trim((string)($query['from']??''));$to=$defaults?$today:trim((string)($query['to']??''));$property=trim((string)($query['property']??''));$status=trim((string)($query['status']??''));if($status==='')$status='all';$errors=[];
  $valid=static function(string $value):bool{$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$date!==false&&$date->format('Y-m-d')===$value;};
  if(!$valid($from))$errors['from']='Enter a valid From Date.';if(!$valid($to))$errors['to']='Enter a valid To Date.';
  if($errors===[]&&$from>$to)$errors['range']='From Date must be on or before To Date.';
  if($errors===[]&&((new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days??0)>3660)$errors['range']='Choose a date range of ten years or less.';
  if($property!==''&&!in_array($property,array_column($properties,'public_id'),true))$errors['property']='Choose an active community or All Properties.';
  if(!in_array($status,['all','open'],true))$errors['status']='Choose All Audits or Open Only.';
  $safeFrom=$valid($from)?$from:$today;$safeTo=$valid($to)?$to:$today;
  return new self($from,$to,$property,$status,$safeFrom.' 00:00:00',(new \DateTimeImmutable($safeTo))->modify('+1 day')->format('Y-m-d').' 00:00:00',$errors);
 }
 public function query():string{return http_build_query(['from'=>$this->from,'to'=>$this->to,'property'=>$this->propertyPublicId,'status'=>$this->status]);}
}
