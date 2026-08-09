# Operations Overview

Operations Overview is a read-only reporting workspace for users with effective `operations` category access. Overview permission answers “What is happening?”; workflow permission answers “What can I do about it?” Operations access does not grant Property Access or `rm-corrections` processing authority.

RM Corrections is the first Overview module. It reads the authoritative `rm_correction_requests` and append-only history records; it creates no reporting copies, email, notifications, history, audits, or workflow changes. The report filters by the submission timestamp using inclusive local calendar days implemented as a half-open database range. It defaults to the first day of the current Gateway month through the current Gateway date.

All Properties groups active communities alphabetically and sorts each group by newest submission. Corporate and inactive properties are excluded. A selected property is resolved by public ID and is only a reporting filter. Summary counts and rows share the same query result and show each request’s current status, not historical status as of submission.

RM Audits gives Tim an always-available replacement for Kiyana's manually compiled summary email. It reads authoritative `rm_audits` and `rm_audit_history` records, defaults to the current Gateway-local month, filters on the half-open `submitted_at` range and an optional active-community public ID, and groups readable Audit Findings by property. Summary counts are derived from the rendered rows.

Operations RM Audit status is an explicit reporting projection: authoritative `open` and `returned` display as **Open**, while `completed` displays as **Completed**. The shared detail timeline retains the authoritative Submitted, Completed, and Returned events and return comments. This projection grants no workflow authority; Overview has no mutation routes, forms, email, notification, or reporting-copy dependency.

The record-level View link sits beside the projected status and preserves the validated date/property context. The report-level Print action invokes browser printing over the already-authoritative server-rendered result. Scoped print CSS includes report identity, criteria, shared summary, property groups, timestamps, projected status text, and full findings while excluding navigation, filters, and interactive controls. There is no Excel, CSV, email, or scheduled export.

The RM Audits Status filter defaults to **All Audits**, which shows all activity for the selected period and property. **Open Only** shows unresolved audits: authoritative `open` plus `returned`, both projected as Open. The allowlisted `all`/`open` criterion is preserved through View, detail/back navigation, Print, and Download PDF; invalid detail-return criteria fall back to the default report safely. Stored Returned state and authoritative history remain unchanged.

Download PDF uses the same validated From, To, property, and Status criteria and the same authoritative report model as screen and Print. The GET-only Operations route generates an in-memory private PDF through Gateway's centralized Dompdf renderer. Filenames are `RM-Audits_FROM_to_TO.pdf` for All Properties and include a sanitized property name for a selected community; status is intentionally omitted. The endpoint creates no reporting copy or workflow event.

Future Overview modules may follow the same landing-card, filtered report, grouped results, and read-only detail pattern without creating a generalized reporting engine.
