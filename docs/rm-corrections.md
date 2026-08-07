# RM Corrections

RM Corrections lets an authorized community manager request a Rent Manager adjustment, such as removing an automatically assessed late fee, and lets an independently authorized Corporate reviewer process it.

Managers supply `Lot # / Address`, `Tenant Full Name`, and `Correction Request`. Gateway derives the property, submitter, and timestamps from authenticated server context. The original request is immutable and has no delete workflow.

The lifecycle is `Pending Review` to `Approved`, `Denied`, or `More Information Needed`. Approved and Denied are terminal. More Information Needed lets an authorized property user append Additional Information to the same record, returning it to Pending Review. Every submission, decision, and response is append-only history.

Corporate access requires explicit effective `rm-corrections` category membership. Operations, Marketing, Finance, employee class, title, Admin, and Property Access do not imply it. Community routes separately require current Property Access and derive the property from the route.

Reviewer delivery uses `RM_CORRECTIONS_REVIEWER_EMAIL`. Automated and development delivery must use `RM_CORRECTIONS_TEST_MODE=true` and `RM_CORRECTIONS_TEST_EMAIL`; this fail-closed override prevents delivery to production recipients. Decision mail uses the authoritative property `manager_email`, subject to the same test override.

Audit events contain public identifiers, event type, actor public ID, and status transition only. Tenant name, request text, comments, responses, email addresses, raw POST data, and session data are excluded. Business history retains required narrative.

Deploy by configuring the RM Corrections environment values, applying migration `202608060018_rm_corrections`, verifying schema, and granting category membership through Category Access. No user is granted by migration. First certify apply, verify, guarded rollback, and re-apply against `npmgateway_test`; applying to the normal database requires explicit approval.
