<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Support\PublicIdGenerator;
final class CategoryAccessBackfillService
{
    public function __construct(private readonly CategoryAccessStoreInterface $store,private readonly InitializationTransactionInterface $transaction,private readonly AuditService $audits,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly array $categories){}
    public function run():array
    {
        if(array_keys($this->categories)!==['operations','human-resources','company-notices','application-reviews','finance','marketing','admin','credit-cards'])throw new \RuntimeException('The approved category allowlist is unavailable or invalid.');$expected=['chuck'=>array_values(array_diff(array_keys($this->categories),['application-reviews'])),'tim'=>array_values(array_diff(array_keys($this->categories),['admin','application-reviews']))];$users=[];foreach(['chuck','tim','hayleigh'] as $username){$user=$this->store->findUserByUsername($username);if($user===null||$user['status']!=='active')throw new \RuntimeException("Required active Gateway user {$username} is missing, inactive, or ambiguous.");$users[$username]=$user;}$expected['hayleigh']=['company-notices'];
        $existing=[];foreach($this->store->memberships() as $row)$existing[(int)$row['user_id']][(string)$row['category']]=true;$created=0;$present=0;$timestamp=$this->clock->now()->format('Y-m-d H:i:s');$actor=$users['chuck'];$this->transaction->begin();try{foreach($expected as $username=>$categories){$target=$users[$username];$made=[];foreach($categories as $category){if(isset($existing[(int)$target['id']][$category])){$present++;continue;}$this->store->grant(['public_id'=>$this->ids->generate(),'user_id'=>(int)$target['id'],'category'=>$category,'granted_by_user_id'=>(int)$actor['id'],'granted_at'=>$timestamp]);$created++;$made[]=$category;}if($made!==[])$this->audits->record('admin.category_access_backfilled',(int)$actor['id'],(int)$actor['employee_id'],(string)$actor['public_id'],'Corporate category access backfilled.',['target_user_public_id'=>$target['public_id'],'target_username'=>$target['username'],'categories_created'=>$made,'actor_user_public_id'=>$actor['public_id'],'source'=>'legacy_config_backfill'],$timestamp);}$this->transaction->commit();}catch(\Throwable $e){$this->transaction->rollback();throw $e;}return ['created'=>$created,'already_present'=>$present,'conflicts'=>0,'total'=>$created+$present];
    }
}
