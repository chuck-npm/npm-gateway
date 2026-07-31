<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\CorporateContextStoreInterface;
use NpmGateway\Exceptions\Domain\CorporateContextConflictException;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\CorporateContextResult;
final class CorporateContextService
{
    public function __construct(private readonly CorporateContextStoreInterface $properties,private readonly PublicIdGenerator $publicIds,private readonly AuditService $audit,private readonly ClockInterface $clock){}
    public function ensure(string $source):CorporateContextResult
    {
        $matches=$this->properties->findCorporateIdentifierMatches();
        if($matches!==[]){if(count($matches)===1&&(int)$matches[0]['prop_id']===1&&(string)$matches[0]['property_code']==='CO'&&(string)$matches[0]['slug']==='corporate')return new CorporateContextResult(false,(int)$matches[0]['id'],(string)$matches[0]['public_id']);$conflicts=[];foreach($matches as $row){if((int)$row['prop_id']===1)$conflicts[]='PropID 1';if((string)$row['property_code']==='CO')$conflicts[]='property code CO';if((string)$row['slug']==='corporate')$conflicts[]='slug corporate';}throw new CorporateContextConflictException('Corporate context identifier conflict: '.implode(', ',array_unique($conflicts)).'.');}
        $publicId=$this->publicIds->generate();$property=['public_id'=>$publicId,'prop_id'=>1,'property_code'=>'CO','slug'=>'corporate','display_name'=>'Corporate','status'=>'active','office_phone'=>'+17065874386','manager_email'=>'noc@npmparks.com','ivr_number'=>null,'ivr_routing_email'=>null,'website_url'=>'https://npmpropertiesinc.com','address_line_1'=>'5021 River Rd., Ste. C','city'=>'Columbus','state'=>'GA','postal_code'=>'31904','timezone'=>'America/New_York','created_by'=>null,'updated_by'=>null];$id=$this->properties->insertCorporate($property);$this->audit->recordSystemProperty('system.corporate_context_created',$id,$publicId,'Corporate operational context created.',['property_public_id'=>$publicId,'prop_id'=>1,'property_code'=>'CO','slug'=>'corporate','status'=>'active','initialization_source'=>$source],$this->clock->now()->format('Y-m-d H:i:s'));return new CorporateContextResult(true,$id,$publicId);
    }
}
