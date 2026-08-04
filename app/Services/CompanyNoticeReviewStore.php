<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Support\PublicIdGenerator;
final class CompanyNoticeReviewStore
{
 private array $session;
 /** @var \Closure():int */ private readonly \Closure $now;
 public function __construct(array &$session,private readonly PublicIdGenerator $ids,?\Closure $now=null){$this->session=&$session;$this->now=$now??static fn():int=>time();}
 public function create(int $userId,array $data):string
 {
  if(!isset($this->session['company_notice_reviews'])||!is_array($this->session['company_notice_reviews']))$this->session['company_notice_reviews']=[];foreach($this->session['company_notice_reviews'] as &$review){if(is_array($review)&&($review['owner']??null)===$userId&&($review['state']??'created')==='created')$review['state']='superseded';}unset($review);
  $token=$this->ids->generate();$this->session['company_notice_reviews'][$token]=['owner'=>$userId,'data'=>$data,'expires_at'=>($this->now)()+900,'source'=>$this->ids->generate(),'state'=>'created','published'=>null];return $token;
 }
 /** @return array{status:string,review:?array} */
 public function resolve(string $token,int $userId):array
 {
  $row=$this->session['company_notice_reviews'][$token]??null;if(!is_array($row)||($row['owner']??null)!==$userId)return ['status'=>'unavailable','review'=>null];
  $state=(string)($row['state']??(($row['published']??null)!==null?'published':'created'));
  if($state==='created'&&(int)($row['expires_at']??$row['expires']??0)<=($this->now)()){$state='expired';$this->session['company_notice_reviews'][$token]['state']='expired';}
  return ['status'=>$state,'review'=>$this->session['company_notice_reviews'][$token]];
 }
 public function get(string $token,int $userId):?array{$result=$this->resolve($token,$userId);return in_array($result['status'],['created','published'],true)?$result['review']:null;}
 public function active(int $userId):?array
 {
  foreach(array_reverse($this->session['company_notice_reviews']??[],true) as $token=>$row){if(is_array($row)&&($row['owner']??null)===$userId&&$this->resolve((string)$token,$userId)['status']==='created')return ['token'=>(string)$token,'review'=>$this->session['company_notice_reviews'][$token]];}return null;
 }
 public function complete(string $token,string $publicId):void
 {
  if(($this->session['company_notice_reviews'][$token]['state']??null)!=='created')return;$this->session['company_notice_reviews'][$token]['state']='published';$this->session['company_notice_reviews'][$token]['published']=$publicId;$this->session['company_notice_reviews'][$token]['data']=null;
 }
 public function discardCompose(int $userId,string $composeContext):int
 {
  $removed=0;foreach($this->session['company_notice_reviews']??[] as $token=>$review){if(!is_array($review)||($review['owner']??null)!==$userId||($review['state']??'created')==='published'||($review['data']['compose_context']??null)!==$composeContext)continue;unset($this->session['company_notice_reviews'][$token]);$removed++;}return $removed;
 }
}
