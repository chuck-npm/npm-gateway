<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Exceptions\Domain\InvalidRmCorrectionException;
final class RmCorrectionValidator
{
 public function submission(array $post):array
 {
  $input=['lot_address'=>$this->single((string)($post['lot_address']??'')),'tenant_name'=>$this->single((string)($post['tenant_name']??'')),'correction_request'=>$this->text((string)($post['correction_request']??''))];$errors=[];
  if(!$this->validSingle($input['lot_address'],200))$errors['lot_address']='Enter a valid lot number or address of 200 characters or fewer.';
  if(!$this->validSingle($input['tenant_name'],200))$errors['tenant_name']='Enter a valid tenant full name of 200 characters or fewer.';
  $length=mb_strlen($input['correction_request']);if($length<10||$length>5000||$this->unsafe($input['correction_request']))$errors['correction_request']='Correction Request must be plain text between 10 and 5,000 characters.';
  if($errors)throw new InvalidRmCorrectionException($errors,$input);return $input;
 }
 public function decision(array $post):array
 {
  $input=['decision'=>(string)($post['decision']??''),'comments'=>$this->text((string)($post['comments']??''))];$errors=[];
  if(!in_array($input['decision'],['approved','denied','more_information_needed'],true))$errors['decision']='Choose Approve, Deny, or Need More Info.';
  if($input['comments']===''||mb_strlen($input['comments'])>5000||$this->unsafe($input['comments']))$errors['comments']='Comments are required and must be plain text of 5,000 characters or fewer.';
  if($errors)throw new InvalidRmCorrectionException($errors,$input);return $input;
 }
 public function response(array $post):array
 {
  $input=['additional_information'=>$this->text((string)($post['additional_information']??''))];$errors=[];
  if($input['additional_information']===''||mb_strlen($input['additional_information'])>5000||$this->unsafe($input['additional_information']))$errors['additional_information']='Additional Information is required and must be plain text of 5,000 characters or fewer.';
  if($errors)throw new InvalidRmCorrectionException($errors,$input);return $input;
 }
 private function single(string $v):string{return preg_replace('/\s+/u',' ',trim($v))??'';}
 private function text(string $v):string{return trim(str_replace(["\r\n","\r"],"\n",$v));}
 private function validSingle(string $v,int $max):bool{return $v!==''&&mb_strlen($v)<=$max&&!$this->unsafe($v)&&!str_contains($v,"\n");}
 private function unsafe(string $v):bool{return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$v)===1||preg_match('/<\/?[a-z][^>]*>/i',$v)===1;}
}
