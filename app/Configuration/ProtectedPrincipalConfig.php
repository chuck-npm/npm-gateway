<?php
declare(strict_types=1);
namespace NpmGateway\Configuration;
final readonly class ProtectedPrincipalConfig
{
    private const PUBLIC_ID='/^[0-9A-HJKMNP-TV-Z]{26}$/';
    public function __construct(public string $userPublicId,public string $employeePublicId,public array $requiredCategories){}
    public static function fromArray(array $input,array $allowedCategories):self
    {
        $user=trim((string)($input['user_public_id']??''));$employee=trim((string)($input['employee_public_id']??''));
        if($user!==''&&preg_match(self::PUBLIC_ID,$user)!==1)throw new \InvalidArgumentException('Protected principal user public ID is malformed.');
        if($employee!==''&&preg_match(self::PUBLIC_ID,$employee)!==1)throw new \InvalidArgumentException('Protected principal employee public ID is malformed.');
        if(($user==='')!==($employee===''))throw new \InvalidArgumentException('Both protected principal public IDs must be configured together.');
        $raw=$input['required_categories']??'admin';$categories=is_array($raw)?$raw:preg_split('/\s*,\s*/',(string)$raw,-1,PREG_SPLIT_NO_EMPTY);$categories=array_values(array_unique(array_map('strval',$categories?:[])));
        if($categories===[]||!in_array('admin',$categories,true))throw new \InvalidArgumentException('Protected principal baseline must include admin.');
        foreach($categories as $category)if(!array_key_exists($category,$allowedCategories))throw new \InvalidArgumentException('Unknown protected principal required category.');
        return new self($user,$employee,$categories);
    }
    public function configured():bool{return $this->userPublicId!=='';}
}
