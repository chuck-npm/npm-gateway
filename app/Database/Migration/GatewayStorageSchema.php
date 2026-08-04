<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class GatewayStorageSchema
{
    public const MIGRATION='202608020010_gateway_storage';
    public const STORAGE_INDEXES=['PRIMARY','uq_storage_objects_public_id','uq_storage_objects_provider_key','idx_storage_objects_lifecycle_created','idx_storage_objects_temporary_owner','idx_storage_objects_uploader','idx_storage_objects_sha256','idx_storage_objects_deleted_by'];
    public const LINK_INDEXES=['PRIMARY','uq_notification_storage_objects_public_id','uq_notification_storage_objects_notice_object','idx_notification_storage_objects_notice_role','idx_notification_storage_objects_linked_by'];
    public const STORAGE_FOREIGN_KEYS=['fk_storage_objects_uploaded_by','fk_storage_objects_temporary_owner','fk_storage_objects_deleted_by'];
    public const LINK_FOREIGN_KEYS=['fk_notification_storage_objects_notification','fk_notification_storage_objects_object','fk_notification_storage_objects_linked_by'];
    public const STORAGE_CHECKS=['chk_storage_objects_provider','chk_storage_objects_provider_container','chk_storage_objects_object_key','chk_storage_objects_filenames','chk_storage_objects_mime_type','chk_storage_objects_byte_size','chk_storage_objects_sha256','chk_storage_objects_lifecycle','chk_storage_objects_lifecycle_metadata'];
}
