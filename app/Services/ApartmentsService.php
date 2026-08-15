<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\{ClockInterface,InitializationTransactionInterface};
use NpmGateway\Exceptions\Domain\InvalidApartmentsWorkbookException;
use NpmGateway\Repositories\ApartmentsRepository;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
final readonly class ApartmentsService
{
 public function __construct(private ApartmentsWorkbookParser$parser,private ApartmentsRepository$repo,private InitializationTransactionInterface$tx,private PublicIdGenerator$ids,private ClockInterface$clock,private AuditService$audit){}
 public function history():array{return$this->repo->history();}
 public function import(array$file,AuthenticatedUser$user):array
 {
  $parsed=$this->parser->parse($file);if($this->repo->hashExists($parsed['file_sha256']))throw new InvalidApartmentsWorkbookException('This Apartments.com file has already been imported.');$names=array_values(array_unique([...array_column($parsed['calls'],'source_property_name'),...array_column($parsed['email_leads'],'source_property_name')]));$map=$this->repo->mappings($names);$unknown=array_values(array_diff($names,array_keys($map)));sort($unknown,SORT_NATURAL|SORT_FLAG_CASE);if($unknown!==[])throw new InvalidApartmentsWorkbookException('The file contains Apartments.com property names that are not configured in Gateway: '.implode(', ',$unknown));$public=$this->ids->generate();$at=$this->clock->now()->format('Y-m-d H:i:s');$calls=count($parsed['calls']);$emails=count($parsed['email_leads']);$this->tx->begin();try{$import=$this->repo->insertImport(['public_id'=>$public,'original_filename'=>$parsed['original_filename'],'file_sha256'=>$parsed['file_sha256'],'user_id'=>$user->id,'source_started_at'=>$parsed['source_started_at'],'source_ended_at'=>$parsed['source_ended_at'],'call_count'=>$calls,'email_count'=>$emails,'at'=>$at]);$this->repo->insertCalls($import,$parsed['calls'],$map,$at,fn():string=>$this->ids->generate());$this->repo->insertEmails($import,$parsed['email_leads'],$map,$at,fn():string=>$this->ids->generate());$this->audit->record('admin.apartments_imported',$user->id,$user->employeeId,$user->publicId,'Apartments.com workbook imported.',['import_public_id'=>$public,'call_count'=>$calls,'email_lead_count'=>$emails,'source_started_at'=>$parsed['source_started_at'],'source_ended_at'=>$parsed['source_ended_at'],'original_filename'=>$parsed['original_filename']],$at);$this->tx->commit();return['calls'=>$calls,'email_leads'=>$emails];}catch(\Throwable$e){$this->tx->rollback();throw$e;}
 }
}
