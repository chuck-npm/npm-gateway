<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class ApplicationReviewsSchema
{
 public const MIGRATION='202608040015_application_reviews';
 public const REVIEW_COLUMNS=['id','public_id','property_id','prospect_name','manager_comments','rm_documents_confirmed','status','submitted_by_user_id','submitted_at','reviewed_by_user_id','reviewed_at','reviewer_comments','created_at','updated_at'];
 public const REVIEW_INDEXES=['PRIMARY','uq_application_reviews_public_id','idx_application_reviews_status_submitted','idx_application_reviews_property_submitted','idx_application_reviews_submitter_submitted','idx_application_reviews_reviewer_reviewed','idx_application_reviews_queue'];
 public const REVIEW_FKS=['fk_application_reviews_property','fk_application_reviews_submitter','fk_application_reviews_reviewer'];
 public const HISTORY_COLUMNS=['id','public_id','application_review_id','event_type','from_status','to_status','acted_by_user_id','comments','created_at'];
 public const HISTORY_INDEXES=['PRIMARY','uq_application_review_history_public_id','idx_application_review_history_review_created','idx_application_review_history_actor_created'];
 public const HISTORY_FKS=['fk_application_review_history_review','fk_application_review_history_actor'];
}
