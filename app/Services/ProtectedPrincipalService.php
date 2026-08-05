<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Configuration\ProtectedPrincipalConfig;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Exceptions\Domain\ProtectedPrincipalViolationException;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class ProtectedPrincipalService
{
    public function __construct(private readonly ProtectedPrincipalConfig $config,private readonly CategoryAccessStoreInterface $store,private readonly AuditService $audits,private readonly ClockInterface $clock){}
    public function configured():bool{return $this->config->configured();}
    public function requiredCategories():array{return $this->config->requiredCategories;}
    public function isProtectedUser(string $publicId):bool{return $this->configured()&&hash_equals($this->config->userPublicId,$publicId);}
    public function isProtectedEmployee(string $publicId):bool{return $this->configured()&&hash_equals($this->config->employeePublicId,$publicId);}
    public function decorate(array $user):array{$protected=$this->isProtectedUser((string)($user['public_id']??''));$user['protected']=$protected;$user['protected_categories']=$protected?$this->requiredCategories():[];return $user;}
    public function assertCategoryBaseline(array $target,array $desired,AuthenticatedUser $actor):void
    {
        if(!$this->isProtectedUser((string)$target['public_id']))return;
        foreach($this->requiredCategories() as $category)if(!isset($desired[$category])){$this->audits->record('security.protected_category_revocation_denied',$actor->id,$actor->employeeId,$actor->publicId,'Protected principal mutation denied.',['actor_user_public_id'=>$actor->publicId,'target_user_public_id'=>$target['public_id'],'attempted_operation'=>'revoke_required_category','category'=>$category,'denial_reason'=>'protected_baseline_required'],$this->clock->now()->format('Y-m-d H:i:s'));throw new ProtectedPrincipalViolationException();}
    }
    public function assertUserMutation(string $targetPublicId,string $operation):void{if($this->isProtectedUser($targetPublicId))throw new ProtectedPrincipalViolationException('protected_identity_mutation_denied');}
    public function assertEmployeeMutation(string $targetPublicId,string $operation):void{if($this->isProtectedEmployee($targetPublicId))throw new ProtectedPrincipalViolationException('protected_employee_mutation_denied');}
    public function health():array
    {
        if(!$this->configured())return ['healthy'=>false,'reasons'=>['not_configured']];$matches=array_values(array_filter($this->store->allUsers(),fn(array $u):bool=>$this->isProtectedUser((string)$u['public_id'])));if(count($matches)!==1)return ['healthy'=>false,'reasons'=>['user_missing_or_ambiguous']];$user=$matches[0];$reasons=[];if(($user['status']??'')!=='active')$reasons[]='user_inactive';if(($user['employee_public_id']??'')!==$this->config->employeePublicId)$reasons[]='employee_link_mismatch';if(($user['employment_status']??'')!=='active')$reasons[]='employee_inactive';if(preg_match('/^[a-z][a-z0-9]{1,49}$/',(string)($user['username']??''))!==1)$reasons[]='username_invalid';$members=[];foreach($this->store->memberships() as $row)if((int)$row['user_id']===(int)$user['id'])$members[]=(string)$row['category'];foreach($this->requiredCategories() as $category)if(!in_array($category,$members,true))$reasons[]='missing_category:'.$category;return ['healthy'=>$reasons===[],'reasons'=>$reasons,'user'=>$user];
    }
}
