<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Support\PublicIdGenerator;
final class OperationsAccessBackfillService
{
 public function __construct(private readonly CategoryAccessStoreInterface $store,private readonly InitializationTransactionInterface $transaction,private readonly AuditService $audits,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly array $categories){}
 public function run():array
 {
  if(array_keys($this->categories)!==['operations','human-resources','company-notices','application-reviews','finance','marketing','admin','credit-cards'])throw new \RuntimeException('The approved Operations category allowlist is unavailable or invalid.');
  $users=[];foreach(['chuck','tim'] as $username){$user=$this->store->findUserByUsername($username);if($user===null||($user['status']??'')!=='active')throw new \RuntimeException("Required active Gateway user {$username} is missing, inactive, or ambiguous.");$users[$username]=$user;}
  $existing=[];foreach($this->store->memberships() as $row)if(($row['category']??'')==='operations')$existing[(int)$row['user_id']]=true;$created=0;$present=0;$timestamp=$this->clock->now()->format('Y-m-d H:i:s');$actor=$users['chuck'];$this->transaction->begin();try{foreach($users as $username=>$target){$id=(int)$target['id'];if(isset($existing[$id])){$present++;continue;}$this->store->grant(['public_id'=>$this->ids->generate(),'user_id'=>$id,'category'=>'operations','granted_by_user_id'=>(int)$actor['id'],'granted_at'=>$timestamp]);$created++;$this->audits->record('admin.operations_access_backfilled',(int)$actor['id'],(int)$actor['employee_id'],(string)$actor['public_id'],'Operations category access backfilled.',['target_user_public_id'=>$target['public_id'],'target_username'=>$username,'category'=>'operations','actor_user_public_id'=>$actor['public_id'],'source'=>'operations_access_backfill'],$timestamp);}$this->transaction->commit();}catch(\Throwable $e){$this->transaction->rollback();throw $e;}return ['created'=>$created,'already_present'=>$present,'conflicts'=>0,'total'=>$created+$present];
 }
}
