# Notifications architecture

Notifications are global authenticated communications. Publication snapshots a complete recipient audience; existing recipient rows are never recalculated when access, assignment, employment details, or email addresses later change.

Migration 008 adds `notifications` and `notification_recipients`. A notice is idempotent by notification type and source public ID. The centralized `NotificationRecipientResolver` and `GatewayNotificationRecipientEligibilityPolicy` allow only active Gateway users backed by active `corporate` or `manager` employees. Repository filtering is verified again by the policy before a transaction starts; an unsafe injected set fails without mutation. Business email is normalized and snapshotted only after eligibility; duplicate eligible addresses share one outbound send result while acknowledgment remains per user. Missing or invalid business email does not prevent an otherwise eligible in-app assignment. Maintenance is excluded, never recorded as `skipped_no_email`.

An acknowledgment means the assigned user read the notice. It is not approval, acceptance, policy consent, or a legal agreement. The outstanding count includes published, acknowledgment-required, assigned, unacknowledged, unexpired notices. Viewing records the immutable first-view timestamp and does not change that count.

For a new employee, only employee name, job title, start date, company phone, business email, and primary property are published. Credentials, username, birth date, personal contact information, comments, and operational internals are prohibited. Onboarding commits first, then sends the existing secure HR credential email. Only after that succeeds does Gateway publish the notice and send non-sensitive company email.

The recipient table is the authoritative delivery and acknowledgment record. Rollback is refused after historical rows exist. A future Admin feature named **Notification Status** may report assignments, acknowledgments, timestamps, and sanitized delivery status. Future notification types should reuse the generic publisher, recipient materialization, query, acknowledgment, and email-status foundation.

Future modules must use the resolver for both broad and targeted audiences. Controllers and email transports must not query employee addresses, construct recipient sets, insert recipient rows, or bypass class eligibility. Eligibility is evaluated for new publication only; classification and activity changes do not rewrite history.

All future company-announcement emails use the shared company announcement definition and renderer. Notification-specific builders supply only the category, title, introduction, ordered field rows, closing, footer, and optional priority treatment; they do not duplicate the branded layout.
