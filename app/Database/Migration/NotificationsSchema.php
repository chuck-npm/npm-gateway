<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class NotificationsSchema
{
    public const MIGRATION='202608020008_notifications';
    public const TABLES=['notifications','notification_recipients'];
    public const NOTIFICATION_INDEXES=['PRIMARY','uq_notifications_public_id','uq_notifications_source','idx_notifications_status_published','idx_notifications_type_published','idx_notifications_created_by'];
    public const RECIPIENT_INDEXES=['PRIMARY','uq_notification_recipients_public_id','uq_notification_recipients_notice_user','idx_notification_recipients_user_ack','idx_notification_recipients_notice_ack','idx_notification_recipients_email_status'];
}
