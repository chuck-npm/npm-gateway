<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Http\AuthenticatedRequestContext;
final class CorporateAccessService
{
    /** @param array<string,list<string>> $access */
    public function __construct(private readonly array $access) {}
    public function allows(?AuthenticatedRequestContext $context):bool
    {
        if($context===null)return false;
        $username=strtolower(trim($context->user->username));
        if(preg_match('/^[a-z][a-z0-9]{1,49}$/',$username)!==1)return false;
        foreach($this->access as $members){
            foreach($members as $member){
                if(hash_equals(strtolower(trim($member)),$username))return true;
            }
        }
        return false;
    }
}
