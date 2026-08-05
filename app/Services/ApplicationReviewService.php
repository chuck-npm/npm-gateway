<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Exceptions\Domain\ApplicationReviewAlreadyCompletedException;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyForbiddenException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Notifications\ApplicationReviewEmailSender;
use NpmGateway\Repositories\ApplicationReviewRepository;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\CommunityActionContext;
final class ApplicationReviewService
{
 public function __construct(private readonly ApplicationReviewRepository $repo,private readonly ApplicationReviewValidator $validator,private readonly InitializationTransactionInterface $tx,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly AuditService $audits,private readonly ApplicationReviewEmailSender $email,private readonly PropertyAccessService $propertyAccess){}
 public function submit(CommunityActionContext $context,array $post):array
 {
  $data=$this->validator->submission($post);$timestamp=$this->clock->now()->format('Y-m-d H:i:s');$public=$this->ids->generate();$this->tx->begin();
  try{if(!$this->propertyAccess->canAccessProperty(new AuthenticatedRequestContext($context->user,''),$context->propertyId))throw new CommunityActionPropertyForbiddenException();$id=$this->repo->insert(['public_id'=>$public,'property_id'=>$context->propertyId,'prospect_name'=>$data['prospect_name'],'manager_comments'=>$data['manager_comments']!==''?$data['manager_comments']:null,'submitted_by_user_id'=>$context->userId,'timestamp'=>$timestamp]);$this->repo->insertHistory(['public_id'=>$this->ids->generate(),'review_id'=>$id,'event_type'=>'submitted','from_status'=>null,'to_status'=>'pending_review','actor_id'=>$context->userId,'comments'=>$data['manager_comments']!==''?$data['manager_comments']:null,'timestamp'=>$timestamp]);$this->audits->record('application_review.submitted',$context->userId,$context->employeeId,$context->userPublicId,'Application review submitted.',['application_review_public_id'=>$public,'property_public_id'=>$context->propertyPublicId,'acting_user_public_id'=>$context->userPublicId,'event'=>'submitted','resulting_status'=>'pending_review'],$timestamp);$this->tx->commit();}catch(\Throwable $e){$this->tx->rollback();throw $e;}
  $review=$this->repo->managerDetail($public,$context->propertyId);$sent=$review!==null&&$this->email->sendSubmission($review);$this->emailAudit($sent,$public,$context->propertyPublicId,$context->user,$timestamp);return ['public_id'=>$public,'email_sent'=>$sent];
 }
 public function decide(string $publicId,array $post,AuthenticatedUser $actor):array
 {
  $data=$this->validator->decision($post);$timestamp=$this->clock->now()->format('Y-m-d H:i:s');$this->tx->begin();
  try{$review=$this->repo->corporateDetail($publicId,true);if($review===null)throw new \OutOfBoundsException();if($review['status']!=='pending_review'||!$this->repo->decide((int)$review['id'],$data['decision'],$actor->id,$data['reviewer_comments'],$timestamp))throw new ApplicationReviewAlreadyCompletedException('This application review has already been completed.');$this->repo->insertHistory(['public_id'=>$this->ids->generate(),'review_id'=>(int)$review['id'],'event_type'=>$data['decision'],'from_status'=>'pending_review','to_status'=>$data['decision'],'actor_id'=>$actor->id,'comments'=>$data['reviewer_comments'],'timestamp'=>$timestamp]);$this->audits->record('application_review.'.$data['decision'],$actor->id,$actor->employeeId,$actor->publicId,'Application review decision recorded.',['application_review_public_id'=>$publicId,'property_public_id'=>$review['property_public_id'],'acting_user_public_id'=>$actor->publicId,'event'=>$data['decision'],'prior_status'=>'pending_review','resulting_status'=>$data['decision']],$timestamp);$this->tx->commit();}catch(\Throwable $e){$this->tx->rollback();throw $e;}
  $completed=$this->repo->corporateDetail($publicId);$sent=$completed!==null&&$this->email->sendDecision($completed);$this->emailAudit($sent,$publicId,(string)$review['property_public_id'],$actor,$timestamp);return ['email_sent'=>$sent];
 }
 private function emailAudit(bool $sent,string $reviewPublic,string $propertyPublic,AuthenticatedUser $actor,string $timestamp):void{try{$this->audits->record($sent?'application_review.email_sent':'application_review.email_failed',$actor->id,$actor->employeeId,$actor->publicId,$sent?'Application Review email sent.':'Application Review email could not be delivered.',['application_review_public_id'=>$reviewPublic,'property_public_id'=>$propertyPublic,'acting_user_public_id'=>$actor->publicId,'delivery_result'=>$sent?'sent':'configuration_or_delivery_failure'],$timestamp);}catch(\Throwable){}}
}
