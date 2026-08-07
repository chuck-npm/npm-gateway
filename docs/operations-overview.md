# Operations Overview

Operations Overview is a read-only reporting workspace for users with effective `operations` category access. Overview permission answers “What is happening?”; workflow permission answers “What can I do about it?” Operations access does not grant Property Access or `rm-corrections` processing authority.

RM Corrections is the first Overview module. It reads the authoritative `rm_correction_requests` and append-only history records; it creates no reporting copies, email, notifications, history, audits, or workflow changes. The report filters by the submission timestamp using inclusive local calendar days implemented as a half-open database range. It defaults to the first day of the current Gateway month through the current Gateway date.

All Properties groups active communities alphabetically and sorts each group by newest submission. Corporate and inactive properties are excluded. A selected property is resolved by public ID and is only a reporting filter. Summary counts and rows share the same query result and show each request’s current status, not historical status as of submission.

Future Overview modules may follow the same landing-card, filtered report, grouped results, and read-only detail pattern without creating a generalized reporting engine.
