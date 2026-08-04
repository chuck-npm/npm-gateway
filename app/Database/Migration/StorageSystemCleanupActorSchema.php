<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class StorageSystemCleanupActorSchema
{
 public const MIGRATION='202608020011_storage_system_cleanup_actor';
 public const BEFORE="((`lifecycle_state` = _utf8mb4'temporary') and (`temporary_review_owner_user_id` is not null) and (`published_at` is null) and (`deleted_at` is null) and (`deleted_by_user_id` is null)) or ((`lifecycle_state` = _utf8mb4'published') and (`temporary_review_owner_user_id` is null) and (`published_at` is not null) and (`deleted_at` is null) and (`deleted_by_user_id` is null)) or ((`lifecycle_state` = _utf8mb4'deleted') and (`temporary_review_owner_user_id` is null) and (`deleted_at` is not null) and (`deleted_by_user_id` is not null))";
 public const AFTER="((`lifecycle_state` = _utf8mb4'temporary') and (`temporary_review_owner_user_id` is not null) and (`published_at` is null) and (`deleted_at` is null) and (`deleted_by_user_id` is null)) or ((`lifecycle_state` = _utf8mb4'published') and (`temporary_review_owner_user_id` is null) and (`published_at` is not null) and (`deleted_at` is null) and (`deleted_by_user_id` is null)) or ((`lifecycle_state` = _utf8mb4'deleted') and (`temporary_review_owner_user_id` is null) and (`deleted_at` is not null))";
 public static function normalize(string $clause):string{return strtolower((string)preg_replace('/\s+/','',str_replace(["\\'",'`','_utf8mb4','(',')'],["'",'','','',''],$clause)));}
}
