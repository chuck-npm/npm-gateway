<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\{ClockInterface,InitializationTransactionInterface};
use NpmGateway\Exceptions\Domain\InvalidCallLogWorkbookException;
use NpmGateway\Repositories\CallLogRepository;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
final readonly class CallLogService
{
 public function __construct(private CallLogWorkbookParser$parser,private CallLogRepository$repo,private InitializationTransactionInterface$tx,private PublicIdGenerator$ids,private ClockInterface$clock,private AuditService$audit,private ?CallLogFilterCriteriaFactory$criteriaFactory=null){}
 public function page(array$query):array{$destinations=$this->repo->activeDestinations();$criteria=($this->criteriaFactory??new CallLogFilterCriteriaFactory())->fromQuery($query,$destinations);$allTotal=$this->repo->totalCalls();$page=$criteria->errors===[]?$this->repo->page($criteria):['rows'=>[],'total'=>0,'page'=>1,'per_page'=>$criteria->perPage,'pages'=>1,'from'=>0,'to'=>0];return$page+['criteria'=>$criteria,'destinations'=>$destinations,'all_total'=>$allTotal];}
 public function import(array$file,AuthenticatedUser$user):int{$parsed=$this->parser->parse($file);if($this->repo->hashExists($parsed['file_sha256']))throw new InvalidCallLogWorkbookException('This Call Log file has already been imported.');$tns=array_values(array_unique(array_column($parsed['rows'],'called_tn')));$destinations=$this->repo->destinations($tns);$unknown=array_values(array_diff($tns,array_keys($destinations)));if($unknown!==[])throw new InvalidCallLogWorkbookException('The file contains Called TN values that are not configured in Gateway: '.implode(', ',$unknown));$public=$this->ids->generate();$at=$this->clock->now()->format('Y-m-d H:i:s');$count=count($parsed['rows']);$this->tx->begin();try{$import=$this->repo->insertImport(['public_id'=>$public,'original_filename'=>$parsed['original_filename'],'file_sha256'=>$parsed['file_sha256'],'uploaded_by_user_id'=>$user->id,'row_count'=>$count,'source_started_at'=>$parsed['source_started_at'],'source_ended_at'=>$parsed['source_ended_at'],'at'=>$at]);$this->repo->insertCalls($import,$parsed['rows'],$destinations,$at,fn():string=>$this->ids->generate());$this->audit->record('admin.call_logs_imported',$user->id,$user->employeeId,$user->publicId,'Lumen Call Log imported.',['import_public_id'=>$public,'row_count'=>$count,'source_started_at'=>$parsed['source_started_at'],'source_ended_at'=>$parsed['source_ended_at'],'original_filename'=>$parsed['original_filename']],$at);$this->tx->commit();return$count;}catch(\Throwable$e){$this->tx->rollback();throw$e;}}
}
