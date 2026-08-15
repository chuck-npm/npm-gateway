<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use mysqli_sql_exception;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\PropertyStoreInterface;
use NpmGateway\Contracts\PropertyTransactionInterface;
use NpmGateway\Exceptions\Domain\InvalidPropertyDataException;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class PropertyAdministrationService
{
    public function __construct(private readonly PropertyValidator $validator,private readonly PropertyStoreInterface $properties,private readonly PropertyTransactionInterface $transaction,private readonly PublicIdGenerator $publicIds,private readonly AuditService $audit,private readonly ClockInterface $clock){}
    public function create(array $input,AuthenticatedUser $actor):void
    {
        $property=[];$this->transaction->begin();
        try{[$property,$errors]=$this->validator->validate($input);if($errors!==[])throw new InvalidPropertyDataException($errors,$property);$publicId=$this->publicIds->generate();$property+=['public_id'=>$publicId,'created_by'=>$actor->id,'updated_by'=>$actor->id];$id=$this->properties->insert($property);$this->audit->recordProperty('hr.property_created',$actor->id,$actor->employeeId,$id,$publicId,'Property created.',['property_public_id'=>$publicId,'prop_id'=>$property['prop_id'],'property_name'=>$property['display_name'],'state'=>$property['state'],'status'=>$property['status'],'ivr_routing_configured'=>true],$this->clock->now()->format('Y-m-d H:i:s'));$this->transaction->commit();}
        catch(\Throwable $exception){$this->transaction->rollback();if($exception instanceof mysqli_sql_exception&&$exception->getCode()===1062)throw $this->duplicateViolation($exception,$property);throw $exception;}
    }
    public function find(string$publicId):?array{return$this->properties->findByPublicId($publicId);}
    public function update(string$publicId,array$input,AuthenticatedUser$actor):bool
    {
        $property=$this->properties->findByPublicId($publicId);if($property===null)return false;$validated=[];$this->transaction->begin();
        try{[$validated,$errors]=$this->validator->validate($input,(int)$property['id']);if($errors!==[])throw new InvalidPropertyDataException($errors,$validated);$validated['updated_by']=$actor->id;$this->properties->update((int)$property['id'],$validated);$this->audit->recordProperty('hr.property_updated',$actor->id,$actor->employeeId,(int)$property['id'],$publicId,'Property updated.',['property_public_id'=>$publicId,'prop_id'=>$validated['prop_id'],'property_name'=>$validated['display_name'],'state'=>$validated['state'],'status'=>$validated['status'],'ivr_routing_configured'=>true],$this->clock->now()->format('Y-m-d H:i:s'));$this->transaction->commit();return true;}
        catch(\Throwable$exception){$this->transaction->rollback();if($exception instanceof mysqli_sql_exception&&$exception->getCode()===1062)throw$this->duplicateViolation($exception,$validated);throw$exception;}
    }
    private function duplicateViolation(mysqli_sql_exception $exception,array $property):InvalidPropertyDataException
    {
        $message=$exception->getMessage();$indexes=[
            'uq_properties_prop_id'=>['prop_id','That PropID is already assigned to another property.'],
            'uq_properties_property_code'=>['property_code','That property code is already assigned to another property.'],
            'uq_properties_slug'=>['slug','That slug is already assigned to another property.'],
            'uq_properties_manager_email'=>['manager_email','That manager email is already assigned to another property.'],
            'uq_properties_ivr_number'=>['ivr_number','That IVR phone is already assigned to another property.'],
        ];
        foreach($indexes as$index=>[$field,$safe])if(str_contains($message,$index))return new InvalidPropertyDataException([$field=>$safe],$property);
        return new InvalidPropertyDataException(['form'=>'A unique property value is already in use. Review the permanent identifiers and try again.'],$property);
    }
}
