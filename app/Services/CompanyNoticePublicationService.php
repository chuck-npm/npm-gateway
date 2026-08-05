<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Contracts\StorageObjectStoreInterface;
use NpmGateway\Notifications\CompanyNoticeEmailSender;
use NpmGateway\Repositories\NotificationRepository;
use NpmGateway\Repositories\NotificationRecipientRepository;
use NpmGateway\Repositories\NotificationStorageObjectRepository;
use NpmGateway\Support\CompanyDateFormatter;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
final class CompanyNoticePublicationService
{
 public function __construct(private readonly NotificationRepository $notices,private readonly NotificationRecipientRepository $recipients,private readonly InitializationTransactionInterface $tx,private readonly PublicIdGenerator $ids,private readonly ClockInterface $clock,private readonly CompanyNoticeValidator $validator,private readonly CompanyNoticeEmailSender $email,private readonly AuditService $audits,private readonly CompanyDateFormatter $dates,private readonly CompanyNoticeDiagnosticLogger $diagnostics=new CompanyNoticeDiagnosticLogger(),private readonly ?CompanyNoticeAssetService $assetService=null,private readonly ?NotificationStorageObjectRepository $links=null,private readonly ?StorageObjectStoreInterface $objects=null,private readonly ?RichTextSanitizer $sanitizer=null,private readonly ?NotificationRecipientResolver $resolver=null){}
 private function recipientResolver():NotificationRecipientResolver{return $this->resolver??new NotificationRecipientResolver($this->recipients,new GatewayNotificationRecipientEligibilityPolicy());}
 public function audienceCount():int{return count($this->recipientResolver()->resolveCompanyAudience());}
 public function publish(array $review,AuthenticatedUser $actor):array
 {
  $stage='rich_content_revalidation';
  try{
   $data=$this->validator->validateReviewed($review['data']);if($existing=$this->notices->findBySource('company_notice',$review['source']))return $existing+['already_published'=>true,'data'=>$data];
   $assets=[];$compose=(string)($data['compose_context']??'');$stage='compose_context_validation';if($compose!==''&&$this->assetService!==null){$stage='selected_asset_resolution';$assets=$this->assetService->authorized($compose,$actor);}
   $stage='image_placeholder_validation';$images=$this->sanitizer?->imageReferences((string)$data['rich_message_html'])??[];$selectedImages=[];foreach($assets as $asset)if($asset['asset_role']==='embedded_image')$selectedImages[(string)$asset['public_id']]=true;if(array_diff_key($images,$selectedImages)!==[]||array_diff_key($selectedImages,$images)!==[])throw new \InvalidArgumentException('Embedded image selections do not match the notice body.');
   $stage='recipient_resolution';$resolver=$this->recipientResolver();$audience=$resolver->resolveCompanyAudience();$resolver->requireApproved($audience);$at=$this->clock->now();$timestamp=$at->format('Y-m-d H:i:s');$public=$this->ids->generate();$this->tx->begin();
   try{
    $stage='notification_insert';$id=$this->notices->insertCompanyNotice(['public_id'=>$public,'title'=>$data['title'],'summary'=>$this->validator->summary($data['message']),'payload'=>['message'=>$data['message'],'rich_message_html'=>$data['rich_message_html'],'published_by_name'=>$actor->displayName,'audience_label'=>'All Active Gateway Users'],'requires_acknowledgment'=>$data['requires_acknowledgment']?1:0,'priority'=>$data['priority'],'source_public_id'=>$review['source'],'actor_id'=>$actor->id,'timestamp'=>$timestamp]);
    $stage='recipient_insert';foreach($audience as $recipient)$this->recipients->insert($id,$recipient,$this->ids->generate(),$timestamp);
    $stage='asset_link_insert';foreach(array_values($assets) as $sort=>$asset){if($this->links===null||$this->objects===null)throw new \RuntimeException('Storage publication services are unavailable.');$this->links->insert($id,(int)$asset['id'],$this->ids->generate(),(string)$asset['asset_role'],$sort,$actor->id);$stage='asset_lifecycle_transition';if(!$this->objects->markPublished((int)$asset['id'],$timestamp))throw new \RuntimeException('Storage lifecycle transition failed.');$this->audits->record('storage.object_published',$actor->id,$actor->employeeId,$actor->publicId,'Storage object published with company notice.',['storage_object_public_id'=>$asset['public_id'],'notification_public_id'=>$public,'role'=>$asset['asset_role'],'byte_size'=>(int)$asset['byte_size']],$timestamp);$stage='asset_link_insert';}
    $stage='publication_audit_insert';$this->audits->record('notification.published',$actor->id,$actor->employeeId,$actor->publicId,'Company notification published.',['notification_public_id'=>$public,'notification_type'=>'company_notice','source_entity_public_id'=>$review['source'],'published_recipient_count'=>count($audience)],$timestamp);$this->audits->record('company_notice.published',$actor->id,$actor->employeeId,$actor->publicId,'Company notice published.',['notification_public_id'=>$public,'actor_user_public_id'=>$actor->publicId,'priority'=>$data['priority'],'requires_acknowledgment'=>$data['requires_acknowledgment'],'materialized_recipient_count'=>count($audience),'asset_count'=>count($assets)],$timestamp);$stage='transaction_commit';$this->tx->commit();
   }catch(\Throwable $error){$this->tx->rollback();throw $error;}
   if($compose!==''&&$this->assetService!==null)$this->assetService->releasePublished($compose,$actor,$assets);return ['id'=>$id,'public_id'=>$public,'already_published'=>false,'data'=>$data,'assets'=>$assets,'published_at'=>$at,'timestamp'=>$timestamp];
  }catch(\Throwable $error){$category=$error instanceof \NpmGateway\Exceptions\Domain\IneligibleNotificationRecipientException?'ineligible_employee_class':($error instanceof \mysqli_sql_exception?'database_failure':($error instanceof \JsonException?'payload_encoding_failure':($stage==='image_placeholder_validation'?'selected_image_missing_from_content':'publication_failure')));$this->diagnostics->record($stage,$error,$category,isset($public)?$public:null);throw $error;}
 }
 public function dispatch(array $publication,AuthenticatedUser $actor):array
 {
  if($publication['already_published'])return ['sent'=>0,'failed'=>0,'reporting_failed'=>false];$sent=0;$failed=0;$stage='email_dispatch';
  try{foreach($this->recipients->groups((int)$publication['id']) as $group){$data=$publication['data'];$at=$publication['published_at'];$ok=$this->email->send($group['business_email_snapshot'],['title'=>$data['title'],'message'=>$data['message'],'rich_message_html'=>$data['rich_message_html'],'assets'=>$publication['assets']??[],'published_by'=>$actor->displayName,'published_at'=>$this->dates->format($at->format('Y-m-d')).' at '.$at->format('g:i A'),'priority'=>$data['priority'],'requires_acknowledgment'=>$data['requires_acknowledgment']]);$stage='delivery_status_update';$ids=array_map('intval',explode(',',$group['recipient_ids']));$this->recipients->markEmail($ids,$ok,$publication['timestamp']);$ok?$sent++:$failed++;$stage='email_dispatch';}$event=$failed?'notification.email_dispatch_failed':'notification.email_dispatch_completed';$stage='delivery_status_update';$this->audits->record($event,$actor->id,$actor->employeeId,$actor->publicId,'Company announcement email dispatch finished.',['notification_public_id'=>$publication['public_id'],'notification_type'=>'company_notice','email_sent_count'=>$sent,'email_failed_count'=>$failed],$publication['timestamp']);return ['sent'=>$sent,'failed'=>$failed,'reporting_failed'=>false];}catch(\Throwable $error){$this->diagnostics->record($stage,$error,'post_commit_reporting_failure',$publication['public_id']);return ['sent'=>$sent,'failed'=>$failed,'reporting_failed'=>true];}
 }
}
