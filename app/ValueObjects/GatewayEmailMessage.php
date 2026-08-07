<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final class GatewayEmailMessage
{
 /** @param list<array{label:string,value:string,emphasized?:bool}> $summaryRows @param list<array{title:string,body:string}|array{title:string,trusted_sanitized_html:string,plain_text:string}> $sections */
 public function __construct(
  public readonly string $preheader,
  public readonly string $eyebrow,
  public readonly string $title,
  public readonly ?string $subtitle=null,
  public readonly ?string $statusLabel=null,
  public readonly string $statusTone='neutral',
  public readonly array $summaryRows=[],
  public readonly array $sections=[],
  public readonly ?string $primaryActionLabel=null,
  public readonly ?string $primaryActionUrl=null,
  public readonly ?string $footerNote=null
 ){
  if($title===''||$eyebrow==='')throw new \InvalidArgumentException('Gateway email title and context are required.');
  if(!in_array($statusTone,['pending','success','danger','informational','neutral'],true))throw new \InvalidArgumentException('Unsupported Gateway email status tone.');
  if(($primaryActionLabel===null)!==($primaryActionUrl===null))throw new \InvalidArgumentException('Gateway email action label and URL must be supplied together.');
  foreach($summaryRows as $row){if(!is_array($row)||!in_array(array_keys($row),[['label','value'],['label','value','emphasized']],true)||!is_string($row['label'])||!is_string($row['value'])||(isset($row['emphasized'])&&!is_bool($row['emphasized'])))throw new \InvalidArgumentException('Invalid Gateway email summary row.');}
  foreach($sections as $section){
   $plain=is_array($section)&&array_keys($section)===['title','body']&&is_string($section['title'])&&is_string($section['body']);
   $trusted=is_array($section)&&array_keys($section)===['title','trusted_sanitized_html','plain_text']&&is_string($section['title'])&&is_string($section['trusted_sanitized_html'])&&is_string($section['plain_text']);
   if(!$plain&&!$trusted)throw new \InvalidArgumentException('Invalid Gateway email section.');
  }
 }
}
