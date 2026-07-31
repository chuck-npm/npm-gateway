<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\PropertyStoreInterface;
use NpmGateway\Support\PhoneFormatter;
final class PropertyValidator
{
    private const STATES=['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY','DC'];
    private const TIMEZONES=['America/New_York','America/Chicago'];
    public function __construct(private readonly PropertyStoreInterface $properties,private readonly PhoneFormatter $phones){}
    public function validate(array $input):array
    {
        $v=[];foreach($input as $k=>$value)$v[$k]=trim((string)$value);$v['property_code']=strtoupper($v['property_code']??'');$v['state']=strtoupper($v['state']??'');$v['manager_email']=strtolower($v['manager_email']??'');$v['ivr_routing_email']=strtolower($v['ivr_routing_email']??'');$v['status']=strtolower($v['status']??'');$e=[];
        $prop=filter_var($v['prop_id']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($prop===false)$e['prop_id']='PropID must be a positive integer.';else{$v['prop_id']=(int)$prop;if($v['prop_id']===1)$e['prop_id']='PropID 1 is reserved for the Corporate operational context.';elseif($this->properties->propIdExists($v['prop_id']))$e['prop_id']='That PropID is already assigned to another property.';}
        if(preg_match('/^[A-Z]{2}$/',$v['property_code']??'')!==1)$e['property_code']='Property Code must contain exactly two letters.';elseif($v['property_code']==='CO')$e['property_code']='Property code CO is reserved for the Corporate operational context.';elseif($this->properties->propertyCodeExists($v['property_code']))$e['property_code']='That property code is already assigned to another property.';
        if(($v['display_name']??'')===''||strlen($v['display_name'])>150)$e['display_name']='Property Name is required and must not exceed 150 characters.';
        if(preg_match('/^[a-z][a-z0-9-]{0,63}$/',$v['slug']??'')!==1)$e['slug']='Slug must be lowercase and URL-safe.';elseif($v['slug']==='corporate')$e['slug']='Slug corporate is reserved for the Corporate operational context.';elseif($this->properties->slugExists($v['slug']))$e['slug']='That slug is already assigned to another property.';
        if(!in_array($v['status']??'',['active','inactive'],true))$e['status']='Select Active or Inactive.';
        foreach(['address_line_1'=>'Street Address','city'=>'City'] as $key=>$label)if(($v[$key]??'')==='')$e[$key]="{$label} is required.";
        if(!in_array($v['state']??'',self::STATES,true))$e['state']='Select a valid two-letter US state.';
        if(preg_match('/^\d{5}(?:-\d{4})?$/',$v['postal_code']??'')!==1)$e['postal_code']='Enter a valid ZIP Code.';
        if(!in_array($v['timezone']??'',self::TIMEZONES,true))$e['timezone']='Select an approved IANA timezone.';
        foreach(['office_phone'=>'Phone','ivr_number'=>'IVR Phone'] as $key=>$label){$phone=$this->phones->normalize($v[$key]??'');if($phone===null)$e[$key]="Enter a valid {$label}.";else$v[$key]=$phone;}
        foreach(['manager_email'=>'Manager Email','ivr_routing_email'=>'IVR Routing Email'] as $key=>$label){if(str_contains($v[$key]??'',"\r")||str_contains($v[$key]??'',"\n")||filter_var($v[$key]??'',FILTER_VALIDATE_EMAIL)===false)$e[$key]="Enter a valid {$label}.";}
        if(!isset($e['manager_email'])&&$this->properties->managerEmailExists($v['manager_email']))$e['manager_email']='That manager email is already assigned to another property.';
        if(!isset($e['ivr_number'])&&$this->properties->ivrNumberExists($v['ivr_number']))$e['ivr_number']='That IVR phone is already assigned to another property.';
        $website=$v['website_url']??'';if($website!==''){if(filter_var($website,FILTER_VALIDATE_URL)===false||!in_array(strtolower((string)parse_url($website,PHP_URL_SCHEME)),['http','https'],true))$e['website_url']='Website must be a valid HTTP or HTTPS URL.';}else$v['website_url']=null;
        return [$v,$e];
    }
}
