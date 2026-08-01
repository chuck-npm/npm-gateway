<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
final class CorporateAccessService
{
    private readonly ?CategoryAccessStoreInterface $access;
    private readonly array $categories;
    public function __construct(CategoryAccessStoreInterface|array|null $access=null,array $categories=[])
    {
        if(is_array($access)&&$access!==[])throw new \InvalidArgumentException('Username-based Corporate access configuration is not supported.');
        $this->access=$access instanceof CategoryAccessStoreInterface?$access:null;$this->categories=$categories;
    }
    public function canAccessCategory(?AuthenticatedRequestContext $context,string $category):bool
    {
        $category=strtolower(trim($category));if($context===null||$this->access===null||preg_match('/^[a-z][a-z0-9-]*$/',$category)!==1||!array_key_exists($category,$this->categories))return false;return $this->access->hasEffectiveMembership($context->user->id,$category);
    }
    public function hasAnyCorporateAccess(?AuthenticatedRequestContext $context):bool{foreach(array_keys($this->categories) as $category)if($this->canAccessCategory($context,(string)$category))return true;return false;}
}
