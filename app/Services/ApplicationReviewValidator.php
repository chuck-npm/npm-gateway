<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Exceptions\Domain\InvalidApplicationReviewException;
final class ApplicationReviewValidator
{
 public function submission(array $post):array
 {
  $name=preg_replace('/\s+/u',' ',trim((string)($post['prospect_name']??'')))??'';$comments=$this->text((string)($post['manager_comments']??''));$errors=[];
  if($name===''||mb_strlen($name)>200||preg_match('/[\x00-\x1F\x7F]/u',$name))$errors['prospect_name']='Enter a valid prospect full name of 200 characters or fewer.';
  if(mb_strlen($comments)>5000||$this->unsafe($comments))$errors['manager_comments']='Manager comments must be plain text of 5,000 characters or fewer.';
  if(($post['rm_documents_confirmed']??null)!=='confirmed')$errors['rm_documents_confirmed']='Confirm that the application and supporting documents are in Rent Manager.';
  $input=['prospect_name'=>$name,'manager_comments'=>$comments,'rm_documents_confirmed'=>(string)($post['rm_documents_confirmed']??'')];if($errors)throw new InvalidApplicationReviewException($errors,$input);return $input;
 }
 public function decision(array $post):array
 {
  $decision=(string)($post['decision']??'');$comments=$this->text((string)($post['reviewer_comments']??''));$errors=[];if(!in_array($decision,['approved','denied'],true))$errors['decision']='Choose Approve or Deny.';if($comments==='')$errors['reviewer_comments']='Review Notes are required before approving or denying an application.';elseif(mb_strlen($comments)>5000||$this->unsafe($comments))$errors['reviewer_comments']='Review Notes must be plain text of 5,000 characters or fewer.';$input=['decision'=>$decision,'reviewer_comments'=>$comments];if($errors)throw new InvalidApplicationReviewException($errors,$input);return $input;
 }
 private function text(string $value):string{return trim(str_replace(["\r\n","\r"],"\n",$value));}
 private function unsafe(string $value):bool{return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$value)===1||preg_match('/<\/?[a-z][^>]*>/i',$value)===1;}
}
