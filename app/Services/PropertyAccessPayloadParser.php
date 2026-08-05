<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class PropertyAccessPayloadParser
{
 public function parse(array $post):array{$employees=$post['employees']??null;$access=$post['access']??[];if(!is_array($employees)||$employees===[]||!array_is_list($employees)||!is_array($access))throw new \InvalidArgumentException('Invalid Property Access submission.');$normalized=[];foreach($employees as $id){if(!is_string($id)||preg_match('/^[A-Z0-9]{26}$/',$id)!==1)throw new \InvalidArgumentException('One or more submitted employees could not be validated.');$normalized[]=$id;}if(count($normalized)!==count(array_unique($normalized)))throw new \InvalidArgumentException('Duplicate employee rows were submitted.');$selected=[];foreach($access as $employee=>$properties){if(!is_string($employee)||!in_array($employee,$normalized,true)||!is_array($properties))throw new \InvalidArgumentException('Invalid Property Access submission.');foreach($properties as $property=>$value){if(!is_string($property)||preg_match('/^[A-Z0-9]{26}$/',$property)!==1||$value!=='1')throw new \InvalidArgumentException('Invalid property selection.');$selected[$employee][$property]=true;}}foreach($normalized as $id)$selected[$id]??=[];return ['employees'=>$normalized,'access'=>$selected];}
}
