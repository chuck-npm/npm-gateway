<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class StorageObjectKeyGenerator
{
 public function generate(string $prefix,string $filename,?\DateTimeImmutable $now=null):string
 {
  $prefix=trim(str_replace('\\','/',$prefix),'/').'/';if(!preg_match('#^[a-z0-9/_-]+/$#D',$prefix))throw new \InvalidArgumentException('Storage prefix is invalid.');$base=trim(basename(str_replace('\\','/',$filename)));$extension=strtolower((string)pathinfo($base,PATHINFO_EXTENSION));$rawStem=(string)pathinfo($base,PATHINFO_FILENAME);$stem=preg_replace('/[^A-Za-z0-9]+/','_',$rawStem)??'';$stem=trim(preg_replace('/_+/','_',$stem)??'','_');if($stem==='')$stem='file';$extension=preg_match('/^[a-z0-9]{1,10}$/D',$extension)?$extension:'';$safe=substr($stem,0,160).($extension!==''?'.'.$extension:'');$utc=($now??new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('UTC'));$token=strtoupper(bin2hex(random_bytes(4)));return $prefix.$utc->format('Ymd_His').'_'.$token.'_'.$safe;
 }
}
