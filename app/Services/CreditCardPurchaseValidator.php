<?php
declare(strict_types=1);
namespace NpmGateway\Services;

use NpmGateway\Exceptions\Domain\InvalidCreditCardPurchaseException;

final class CreditCardPurchaseValidator
{
 public function validate(array $post,?\DateTimeImmutable $now=null):array
 {
  $in=['card_last_four'=>trim((string)($post['card_last_four']??'')),'purchase_date'=>trim((string)($post['purchase_date']??'')),'amount'=>trim((string)($post['amount']??'')),'purchased_by_employee_public_id'=>trim((string)($post['purchased_by_employee_public_id']??'')),'vendor'=>trim((string)($post['vendor']??'')),'description'=>trim((string)($post['description']??'')),'receipt_missing'=>($post['receipt_missing']??'')==='1','missing_receipt_reason'=>trim((string)($post['missing_receipt_reason']??''))];$e=[];
  if(preg_match('/^[0-9]{4}$/D',$in['card_last_four'])!==1)$e['card_last_four']='Enter exactly four digits.';
  $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$in['purchase_date']);if(!$date||$date->format('Y-m-d')!==$in['purchase_date'])$e['purchase_date']='Enter a valid purchase date.';elseif($date>($now??new \DateTimeImmutable()))$e['purchase_date']='Purchase date cannot be in the future.';
  if(preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/D',$in['amount'],$amount)!==1||($amount[1]==='0'&&trim((string)($amount[2]??''),'0')===''))$e['amount']='Enter a positive amount with no more than two decimal places.';else $in['amount']=$amount[1].'.'.str_pad((string)($amount[2]??''),2,'0');
  if(preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/',$in['purchased_by_employee_public_id'])!==1)$e['purchased_by_employee_public_id']='Choose who made the purchase.';
  foreach(['vendor'=>[2,150,'Purchased At is required.'],'description'=>[3,2000,'Description is required.']] as $key=>[$min,$max,$message]){if(mb_strlen($in[$key])<$min)$e[$key]=$message;elseif(mb_strlen($in[$key])>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|<[^>]*>/u',$in[$key]))$e[$key]='Enter safe plain text within the allowed length.';}
  if($in['receipt_missing']&&mb_strlen($in['missing_receipt_reason'])<5)$e['missing_receipt_reason']='Explain why the receipt is unavailable.';elseif(mb_strlen($in['missing_receipt_reason'])>1000||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|<[^>]*>/u',$in['missing_receipt_reason']))$e['missing_receipt_reason']='Enter a safe plain-text explanation within the allowed length.';
  if($e)throw new InvalidCreditCardPurchaseException($e,$in);return $in;
 }
 public function validateCorrection(array $post,string $receiptStatus,?\DateTimeImmutable $now=null):array
 {
  $post['receipt_missing']=$receiptStatus==='missing'?'1':'0';if($receiptStatus!=='missing')$post['missing_receipt_reason']='';
  try{$in=$this->validate($post,$now);$errors=[];}catch(InvalidCreditCardPurchaseException $e){$in=$e->input;$errors=$e->errors;}
  $in['correction_reason']=trim((string)($post['correction_reason']??''));$in['record_version']=trim((string)($post['record_version']??''));$length=mb_strlen($in['correction_reason']);
  if($length<10)$errors['correction_reason']='Briefly explain the correction using at least 10 characters.';elseif($length>500||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|<[^>]*>/u',$in['correction_reason']))$errors['correction_reason']='Enter a safe plain-text correction reason of no more than 500 characters.';
  if(preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/',$in['record_version'])!==1)$errors['record_version']='This purchase was updated by someone else. Review the latest information before making another correction.';
  if($errors)throw new InvalidCreditCardPurchaseException($errors,$in);return $in;
 }
}
