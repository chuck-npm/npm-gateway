# RM Audits

RM Audits let Corporate record missing or incomplete Rent Manager tenant-file items for a real community, tenant, and unit. The manager corrects Rent Manager and marks the audit Completed; Corporate may return that same record with comments for another correction cycle.

The Community Actions RM Audits card shows manager attention as `Open + Returned` for the trusted current property. Completed and other-property audits are excluded. A positive count displays `1 Needs Attention` or `{n} Need Attention`; zero displays no pill. This is actionable work, not a total-record metric, and is queried live rather than persisted.

Access is controlled by the standalone `rm-audit` Corporate category. It is independent of job title and every other Corporate or Property Access grant. Community Actions access requires a current active-community Property Access grant and all audit lookup is property-scoped.

The authoritative lifecycle is `open -> completed -> returned -> completed`. History is append-only (`Submitted`, `Completed`, `Returned`), and every repeated completion is retained. Records cannot be deleted or edited in v1. Conditional updates under a row lock reject stale or double submissions.

Audit Findings use the vendored Quill 2.0.3 editor with bold, italic, underline, ordered/bullet lists, indentation, links, and clear formatting. A textarea remains functional without JavaScript. Server-side sanitization permits only `p`, `br`, `strong`/`b`, `em`/`i`, `u`, `ol`, `ul`, `li`, and `a`; only HTTP(S) links survive. Media, scripts, styles, handlers, unsafe schemes, arbitrary attributes/classes, and unknown markup are removed. Canonical plain text is derived server-side. Attachments are not supported.

Submitted and Returned messages go only to the authoritative property's valid `manager_email`. Corporate test mode never redirects those messages. Completed messages use `RM_AUDIT_CORPORATE_TEST_EMAIL` when `RM_AUDIT_CORPORATE_TEST_MODE=true`; otherwise they use `RM_AUDIT_REVIEWER_EMAIL`. Missing or invalid recipients fail closed. Delivery is post-commit, and delivery failure does not undo business state.

Deployment requires explicit approval to apply migration `202608070019_rm_audits` to the normal database. Configure the three RM Audit email variables, apply and verify the migration, explicitly assign `rm-audit` access, and perform the Pine Hill manual cycle: submit, complete, return with comments, complete again. Verify one audit, history `Submitted / Completed / Returned / Completed`, manager-bound delivery to the property mailbox, and both completion notices to the configured Corporate test recipient. Automated tests must use fake delivery.
