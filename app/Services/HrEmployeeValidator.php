<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\HrEmployeeStoreInterface;
use NpmGateway\Contracts\UserStoreInterface;
use NpmGateway\Exceptions\Domain\InvalidHrEmployeeDataException;
use NpmGateway\Support\PhoneFormatter;
final class HrEmployeeValidator
{
    public function __construct(private readonly HrEmployeeStoreInterface $employees,private readonly UserStoreInterface $users,private readonly PhoneFormatter $phones){}
    public function validate(array $input):array
    {
        $safe=[];foreach(['first_name','last_name','job_title','employee_type','employment_status','start_date','company_phone','business_email','personal_phone','personal_email','property_id','username','comments'] as $key)$safe[$key]=is_scalar($input[$key]??null)?(string)$input[$key]:'';
        $errors=[];foreach(['first_name'=>75,'last_name'=>75,'job_title'=>100] as $key=>$max){$safe[$key]=trim($safe[$key]);if($safe[$key]===''||strlen($safe[$key])>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$safe[$key]))$errors[$key]='This field is required and must contain valid plain text.';}
        if(!in_array($safe['employee_type'],['corporate','manager','assistant_manager'],true))$errors['employee_type']='Select Corporate, Manager, or Assistant Manager.';
        if(!in_array($safe['employment_status'],['active','inactive'],true))$errors['employment_status']='Select a valid employment status.';
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$safe['start_date']);if(!$date||$date->format('Y-m-d')!==$safe['start_date'])$errors['start_date']='Enter a valid start date.';
        foreach(['company_phone','personal_phone'] as $key){$normalized=$this->phones->normalize($safe[$key]);if($normalized===null)$errors[$key]='Enter a valid 10-digit US phone number.';else $safe[$key]=$normalized;}
        foreach(['business_email','personal_email'] as $key){$safe[$key]=strtolower(trim($safe[$key]));if(strlen($safe[$key])>254||str_contains($safe[$key],"\r")||str_contains($safe[$key],"\n")||filter_var($safe[$key],FILTER_VALIDATE_EMAIL)===false)$errors[$key]='Enter a valid email address.';}
        $safe['username']=strtolower(trim($safe['username']));if(preg_match('/^[a-z][a-z0-9]{1,49}$/',$safe['username'])!==1)$errors['username']='Use 2–50 lowercase letters or digits, beginning with a letter.';elseif($this->users->usernameExists($safe['username']))$errors['username']='That permanent Gateway username is already in use.';
        $safe['comments']=trim($safe['comments']);if($safe['comments']===''||strlen($safe['comments'])>65535)$errors['comments']='Employee Notes are required and must not exceed 65,535 bytes.';
        $property=null;if($safe['employee_type']==='corporate'){$safe['property_id']='';$property=$this->employees->corporateProperty();if($property===null)$errors['employee_type']='The protected Corporate operational context is unavailable.';}
        else{$id=filter_var($safe['property_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$property=$id===false?null:$this->employees->findOperationalProperty((int)$id);if($property===null||$property['status']!=='active'||((int)$property['prop_id']===1&&$property['property_code']==='CO'&&$property['slug']==='corporate'))$errors['property_id']='Select an active community property.';}
        if($errors!==[])throw new InvalidHrEmployeeDataException($errors,$safe);$safe['property']=$property;$safe['employee_class']=$safe['employee_type']==='corporate'?'corporate':'manager';$safe['assignment_type']=$safe['employee_type']==='assistant_manager'?'assistant_manager':($safe['employee_type']==='manager'?'property_manager':'corporate');return $safe;
    }
}
