<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class RmCorrectionsSchema
{
 public const MIGRATION='202608060018_rm_corrections';
 public const CATEGORIES=['operations','human-resources','company-notices','application-reviews','rm-corrections','finance','marketing','admin','credit-cards'];
 public const REQUEST_COLUMNS=['id','public_id','property_id','submitted_by_user_id','lot_address','tenant_name','correction_request','status','submitted_at','updated_at','reviewed_by_user_id','reviewed_at'];
 public const HISTORY_COLUMNS=['id','public_id','rm_correction_request_id','event_type','actor_user_id','comments','created_at'];
}
