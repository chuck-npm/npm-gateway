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
        catch(\Throwable $exception){$this->transaction->rollback();if($exception instanceof mysqli_sql_exception&&$exception->getCode()===1062){$message=$exception->getMessage();$field=str_contains($message,'uq_properties_property_code')?'property_code':(str_contains($message,'uq_properties_slug')?'slug':(str_contains($message,'uq_properties_manager_email')?'manager_email':(str_contains($message,'uq_properties_ivr_number')?'ivr_number':'prop_id')));$safe=['prop_id'=>'That PropID is already assigned to another property.','property_code'=>'That property code is already assigned to another property.','slug'=>'That slug is already assigned to another property.','manager_email'=>'That manager email is already assigned to another property.','ivr_number'=>'That IVR phone is already assigned to another property.'];throw new InvalidPropertyDataException([$field=>$safe[$field]],$property); }throw $exception;}
    }
}
