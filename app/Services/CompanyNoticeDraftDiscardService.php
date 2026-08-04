<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class CompanyNoticeDraftDiscardService
{
 public function __construct(private readonly CompanyNoticeComposeStore $composes,private readonly CompanyNoticeAssetService $assets,private readonly CompanyNoticeReviewStore $reviews,private readonly AuditService $audits,private readonly ClockInterface $clock){}
 /** @return array{status:string,attachment_count:int,embedded_image_count:int} */
 public function discard(string $composeContext,AuthenticatedUser $actor):array
 {
  $compose=$this->composes->current($actor->id);if($compose===null||!hash_equals((string)$compose['id'],$composeContext))return ['status'=>'unavailable','attachment_count'=>0,'embedded_image_count'=>0];
  $selected=$this->assets->authorized($composeContext,$actor);$attachments=0;$images=0;foreach($selected as $asset)(($asset['asset_role']??'')==='embedded_image')?$images++:$attachments++;
  $at=$this->clock->now()->format('Y-m-d H:i:s');try{foreach($selected as $asset)if(!$this->assets->remove($composeContext,(string)$asset['public_id'],$actor,$at))return ['status'=>'failed','attachment_count'=>$attachments,'embedded_image_count'=>$images];}catch(\Throwable){return ['status'=>'failed','attachment_count'=>$attachments,'embedded_image_count'=>$images];}
  $this->reviews->discardCompose($actor->id,$composeContext);$this->composes->discard($composeContext,$actor->id);
  $this->audits->record('company_notice.draft_discarded',$actor->id,$actor->employeeId,$actor->publicId,'Company notice draft discarded.',['publisher_public_id'=>$actor->publicId,'compose_context_id'=>$composeContext,'attachment_count'=>$attachments,'embedded_image_count'=>$images],$at);
  return ['status'=>'discarded','attachment_count'=>$attachments,'embedded_image_count'=>$images];
 }
}
