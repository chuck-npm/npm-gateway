# Company Notices workflow

Company Notices is the authorized Corporate publishing workspace; Notifications is every recipient's inbox. Access uses the `company-notices` database membership. Publishers compose plain text, review a server-side owner-bound short-lived token, and publish once. No drafts are persisted.

Version 1 materializes active-user/active-employee recipients only when their employee class is explicitly `corporate` or `manager`. Preview and publication use the same centralized resolver; Maintenance never affects delivery or acknowledgment totals. Published notices are immutable. Mandatory notices remain outstanding until acknowledgment; non-required notices are informational and record first view without becoming acknowledged. Email uses the shared Company Announcement renderer and existing SMTP configuration. Recipient rows remain the source of truth for delivery, viewing, acknowledgment, and publisher reporting. Drafts and targeted audiences are future work.
