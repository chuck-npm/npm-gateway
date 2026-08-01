<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class CategoryAccessPayloadParser
{
    public function __construct(private readonly array $categories){}
    /** @return array{users:list<string>,access:array<string,array<string,bool>>} */
    public function parse(array $post):array
    {
        $users=$post['users']??null;$access=$post['access']??[];
        if(!is_array($users)||$users===[]||!array_is_list($users)||!is_array($access))throw new \InvalidArgumentException('Invalid category access submission.');
        $normalizedUsers=[];
        foreach($users as $publicId){if(!is_string($publicId)||preg_match('/^[A-Z0-9]{26}$/',$publicId)!==1)throw new \InvalidArgumentException('One or more submitted users could not be validated.');$normalizedUsers[]=$publicId;}
        if(count($normalizedUsers)!==count(array_unique($normalizedUsers)))throw new \InvalidArgumentException('Duplicate user rows were submitted.');
        $normalizedAccess=[];
        foreach($access as $publicId=>$checkboxes){if(!is_string($publicId)||!in_array($publicId,$normalizedUsers,true))throw new \InvalidArgumentException('One or more submitted users could not be validated.');if(!is_array($checkboxes))throw new \InvalidArgumentException('Invalid category access submission.');foreach($checkboxes as $category=>$value){if(!is_string($category)||!array_key_exists($category,$this->categories))throw new \InvalidArgumentException('An unknown category was submitted.');if(!is_string($value)||$value!=='1')throw new \InvalidArgumentException('Invalid category checkbox value.');$normalizedAccess[$publicId][$category]=true;}}
        foreach($normalizedUsers as $publicId)$normalizedAccess[$publicId]??=[];
        return ['users'=>$normalizedUsers,'access'=>$normalizedAccess];
    }
}
