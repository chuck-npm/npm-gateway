<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class CompanyNoticeDiagnosticLogger
{
 private const STAGES=['review_token_load','review_token_validation','compose_context_validation','rich_content_revalidation','selected_asset_resolution','asset_ownership_validation','asset_role_validation','asset_limit_validation','image_placeholder_validation','recipient_resolution','notification_insert','recipient_insert','asset_link_insert','asset_lifecycle_transition','publication_audit_insert','transaction_commit','review_token_completion','email_render','attachment_link_render','cid_image_fetch','cid_image_attach','email_dispatch','delivery_status_update','redirect_result'];
 public function record(string $stage,\Throwable $error,string $category,?string $notificationPublicId=null):void
 {
  if(!in_array($stage,self::STAGES,true))$stage='redirect_result';$entry=['stage'=>$stage,'exception_class'=>$error::class,'failure_category'=>preg_replace('/[^a-z0-9_]/','',strtolower($category))?:'unexpected_failure','timestamp'=>(new \DateTimeImmutable())->format(DATE_ATOM)];if($notificationPublicId!==null)$entry['notification_public_id']=$notificationPublicId;error_log((string)json_encode(['company_notice_publication'=>$entry],JSON_THROW_ON_ERROR));
 }
}
