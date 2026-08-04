<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class Navigation
{
 /** @return list<array{label:string,url:string,active:bool}> */
 /** @param string $projectRoot Absolute project root containing config/navigation.php. */
 public static function forRoute(string $route,string $projectRoot):array
 {
  $items=require $projectRoot.'/config/navigation.php';
  return array_map(static fn(array $item):array=>['label'=>(string)$item['label'],'url'=>(string)$item['url'],'active'=>$item['route']===$route],$items);
 }
}
