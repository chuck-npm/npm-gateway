<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Support\PublicIdGenerator;
final class CompanyNoticeComposeStore
{
 private array $session;/** @var \Closure():int */private readonly \Closure $now;
 public function __construct(array &$session,private readonly PublicIdGenerator $ids,?\Closure $now=null){$this->session=&$session;$this->now=$now??static fn():int=>time();}
 public function active(int $owner):array{$this->cleanup();foreach($this->session['company_notice_composes']??[] as $id=>$row)if(is_array($row)&&($row['owner']??null)===$owner&&($row['expires_at']??0)>($this->now)())return ['id'=>$id]+$row;return $this->create($owner);}
 public function current(int $owner):?array{$this->cleanup();foreach($this->session['company_notice_composes']??[] as $id=>$row)if(is_array($row)&&($row['owner']??null)===$owner)return ['id'=>$id]+$row;return null;}
 public function resolve(string $id,int $owner):?array{$this->cleanup();$row=$this->session['company_notice_composes'][$id]??null;return is_array($row)&&($row['owner']??null)===$owner?['id'=>$id]+$row:null;}
 public function select(string $id,int $owner,string $publicId,string $role):bool{$row=$this->resolve($id,$owner);if($row===null)return false;$assets=$row['assets']??[];$assets[$publicId]=$role;$this->session['company_notice_composes'][$id]['assets']=$assets;$this->session['company_notice_composes'][$id]['expires_at']=($this->now)()+86400;return true;}
 public function remove(string $id,int $owner,string $publicId):void{if($this->resolve($id,$owner)!==null)unset($this->session['company_notice_composes'][$id]['assets'][$publicId]);}
 public function discard(string $id,int $owner):bool{if($this->resolve($id,$owner)===null)return false;unset($this->session['company_notice_composes'][$id]);return true;}
 public function assets(string $id,int $owner):array{return (array)($this->resolve($id,$owner)['assets']??[]);}
 private function create(int $owner):array{$id=$this->ids->generate();$row=['owner'=>$owner,'assets'=>[],'expires_at'=>($this->now)()+86400];$this->session['company_notice_composes'][$id]=$row;return ['id'=>$id]+$row;}
 private function cleanup():void{if(!isset($this->session['company_notice_composes'])||!is_array($this->session['company_notice_composes']))$this->session['company_notice_composes']=[];foreach($this->session['company_notice_composes'] as $id=>$row)if(!is_array($row)||($row['expires_at']??0)<=($this->now)())unset($this->session['company_notice_composes'][$id]);}
}
