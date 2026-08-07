# Gateway Constitution

Business-function access is independent of job title and must be granted through its authoritative access category. Structured rich text must be sanitized before persistence. Manager completion may be trusted while preserving append-only audit history. A Corporate return reuses the same immutable workflow record, and reporting/history preserves every completion and return cycle.

Card-level attention indicators represent actionable work, not general activity totals.

1. Gateway is the system of record for NPM business rules.
2. Each business rule exists exactly once.
3. The database stores facts, application services enforce rules, and clients present results.
4. Meaningful actions are auditable.
5. Secrets are not stored unnecessarily.
6. Presentation layers are replaceable.
7. External integrations are adapters behind application-facing contracts.
8. Gateway preserves institutional knowledge in code and maintained documentation.
9. AI is an optional, non-privileged client using the same services and authorization as every other client.
10. Only application services begin, commit, or roll back transactions (Gateway Rule 013).
11. Services may call services; repositories call neither services nor repositories (Gateway Rule 014).
12. Repositories own persistence, not business policy; services enforce rules and presentation only presents them (Gateway Rule 015).
13. Every business entity has one approved creation service. Commands, controllers, imports, jobs, APIs, and AI clients never write business rows directly (No-Orphans Rule).
14. Future AI clients receive no special access (Gateway Rule 016).
15. Every service is browser-independent and testable without a browser (Gateway Rule 020).

The dependency direction is presentation/client → application service → repository → database. The composition root injects dependencies; business services do not retrieve the container.

Authenticated identity comes only from a validated Gateway database session,
never UI state or a native-session user ID. Clients never infer authorization
from visible controls. Raw passwords and session identifiers are never
persisted. Browser, future API, external, and AI clients cross the same
authentication boundary, and security outcomes remain explainable through
immutable attempt and audit history.

Native PHP sessions and Gateway authentication sessions are distinct. Native
sessions hold CSRF, flash, and temporary UI state under their own cookie;
Gateway's opaque authentication cookie is validated through `SessionService`
and `user_sessions`. A Gateway token is never used as a PHP session ID.

Dashboards present verified facts and truthful empty states; they never invent
operational data to fill space. Navigation exposes only implemented
capabilities, and hidden controls never constitute authorization. Authenticated
identity remains request-scoped, and the presentation layer remains replaceable.

## Property identity and administration

A property is a permanent operational context. Its PropID, two-letter property
code, and URL slug are separate permanent business identifiers assigned once,
never reused, and unaffected by renaming. Existing identifiers are manually
preserved during controlled legacy population. Automatic PropID allocation and
the future 200-number spacing workflow remain deferred.

Addresses stay structured in storage and are rendered as one escaped,
copy-friendly directory value. The universal Properties workspace is read-only;
creation belongs to explicitly username-authorized Human Resources routes.
Manager display derives from the active employee/property assignment and reads
`Not assigned` when no assignment exists; manager names are never duplicated in
property rows.

Corporate is the permanent foundational operational context and uses the same
property model as communities. Its permanent identity is PropID 1, code CO,
and slug `corporate`. A transaction-owned, idempotent foundation service creates
it during Gateway initialization and approved local backfills; split or reused
identifiers fail closed. Ordinary property administration cannot create,
deactivate, delete, or change Corporate's identity. Corporate has no leasing
IVR, while community IVR requirements remain mandatory. Its Manager continues
to derive only from the authoritative employee assignment relationship.

The authoritative manager is the sole active primary `property_manager`
assignment whose employee is active. Non-primary assignments represent support,
floating, temporary, or secondary responsibility and do not populate property
directories. Database invariants allow at most one active primary manager per
property and at most one active primary property per employee; applications
must also validate these rules and translate constraint races safely.
# Operations-home principles

Overview permissions answer “What is happening?” Workflow permissions answer “What can I do about it?” Reporting visibility never grants workflow authority. Reporting views reuse authoritative business records and append-only history rather than copying workflow data.

Gateway home pages prioritize immediate operational access over executive
analytics. Tool cards represent plausible, named business capabilities and
must never advertise unavailable functionality through dead links. Visibility
and authorization are separate concerns: neither the client nor a user may
infer authorization from a visible card. Once implemented, universal
capabilities must be reached through approved application services and real
internal routes.

Unavailable capabilities remain informative, explicitly labeled, and
non-interactive. Home pages must not manufacture activity, counts, trends, or
other data to make a layout appear complete.

Home-page sections may vary according to decisions from the Gateway access
framework so frequent operational tools remain easy to scan. Corporate access
is resolved by an approved access service using permanent, non-reused Gateway
username membership. Employee contact addresses, shared property mailboxes,
employee classification, job title, and property assignment are separate
concepts and do not determine application access. Corporate tools prioritize the broad areas approved users
use most often. Presentation visibility is not final module authorization and
must never be treated as proof of access by a client. Final authorization must
be enforced by approved services or middleware. Disabled capabilities remain
truthful, non-interactive, and destination-free.

The employee directory is operational, not a full HR record, and follows
least-information presentation. Employee and Gateway user identities remain
separate: every Gateway user has an employee, not every employee has a user,
and maintenance employees do not receive Gateway users. Read access never
implies write access. Business-object URLs use stable public identifiers, and
routine directory reads do not create permanent audit noise.

Company Directory visibility is universal for authenticated Gateway users and
is never filtered by corporate access, class, title, assignment, context, or
username. It exposes approved directory information only.

Company Directory means universal approved contact information. Complete
employee records belong to the separately restricted Human Resources domain.
Universal directory access never exposes an employee-detail route, employment
history, detailed assignments, personnel documents, private notes, or HR
administration.

Future employee creation ownership is explicit: corporate-authorized users
create corporate staff, managers, and assistant managers; managers and
assistant managers create maintenance employees in approved contexts.
Managers and assistant managers automatically receive Gateway users;
maintenance employees never do. Maintenance creation triggers approved
corporate contact notification. Transfers preserve the employee and close/open
assignments rather than duplicating identity.

Employee numbers are Gateway-generated, permanent, never reused, and formatted
`NPM######`. Generation must be concurrency-safe using a bounded advisory lock,
transaction, uniqueness recheck, and safe exhaustion at `NPM999999`; unlocked
`MAX + 1` allocation is forbidden. Employee photographs are not part of the
Gateway employee domain.
### HR employee creation boundary

HR employee creation is explicitly authorized through `CorporateAccessService` and is transactional across employee, primary operational assignment where applicable, Gateway user, and creation audit. Corporate context is derived without an assignment; Manager uses `property_manager`; Assistant Manager maps to employee class `manager` and assignment type `assistant_manager`. Date of Birth is required by this workflow but nullable in storage for legacy compatibility. Date of Birth, personal contacts, and Employee Notes never enter directory projections, general search, logs, or routine audit metadata. Personal phone, personal email, and Employee Notes are optional and persist as null when absent. Initial plaintext passwords may cross only the synchronous, post-commit secure-notification boundary and are never persisted.

Age is never stored. A future birthday reminder may derive month and day from `date_of_birth` for active employees only, without exposing birth year or age in general reminders. No separate birthday column or reminder workflow is authorized here.

The production notification transport is an injected PHPMailer SMTP adapter using authenticated TLS with platform CA verification. Unencrypted modes and an incorrect sender are rejected before employee-number allocation. Transport failures are reduced to a safe domain error; raw SMTP errors, credentials, recipients, bodies, and passwords are not logged or exposed. Because the original initial password cannot be recovered from its hash after a failed post-commit send, a future authorized reset workflow—not plaintext retry storage—is the recovery design.
# Corporate category authorization

Operations is a first-class Corporate authorization category and is never inferred from Admin, Finance, Marketing, employee class, or title. Initial backfill membership is Chuck and Tim only; later changes use Category Access.

Application Reviews is a separate first-class Corporate category. Central reviewers require explicit effective `application-reviews` membership; Operations, Admin, employee class, Property Access, username, and identity do not imply it. Managers retain property-scoped Community Actions access, while Corporate reviewers use the function-scoped centralized workspace. Both perspectives operate on the same review and history records.

Corporate cards are universally visible to authenticated users, while destinations and controllers are authorized solely through database-backed category membership. Category configuration defines the fixed vocabulary and display labels only; it must never contain executable username membership lists. Employee class, title, email, assignment, and username do not confer access. Inactive users are denied without deleting their memberships.

Migration 007 is schema-only. Legacy membership backfill is a guarded, transactional, auditable local command. Fresh administrator bootstrap creates all five memberships atomically. Administration must preserve at least one active Admin and must prevent the acting administrator from removing their own Admin membership.

Property assignment and Property Access are distinct authorities. Assignments preserve employment and operational history; explicit database-backed Property Access grants authorize an active Gateway user to an active community. Employee class, Category Access, Admin access, and assignment alone never imply Property Access. Property-scoped controllers must use the centralized PropertyAccessService.

Community Actions are always property-scoped. Every Community Action is recorded and authorized against both the authenticated user and the selected property. The property is derived exclusively from the authorized route context and must never be selected, submitted, or overridden through form data. Property Access is rechecked on every property workspace and action request. A Property Access grant determines which property workspaces a user may open; it does not modify employment assignment history.

Managers perform property-scoped work. Corporate reviews and manages that work through centralized function-scoped workspaces. Both views operate on the same business records and append-only history. Corporate function authorization does not imply manager Property Access, and Property Access does not imply Corporate function authorization.

Workflow access follows business function rather than employee job title. Corporate workflow authorization is category-scoped independently of Property Access. Operational requests may loop through clarification without duplicate records: the original submission stays immutable, while decisions and responses are retained as append-only business history.
# Company communications

Global Notifications are assigned independently of Corporate category access. Acknowledgment proves reading, not legal agreement. Historical audience, first-view, acknowledgment, and delivery evidence must not be silently recalculated or deleted.

Gateway-generated notifications are delivered only to active eligible employees classified as `corporate` or `manager`. `maintenance` employees are never notification recipients and are excluded before recipient materialization, email delivery, acknowledgment tracking, and reporting. Category Access, an email address, a user record, or a property assignment cannot override this platform rule. Unknown future classifications are denied by default.

Emergency Contact Information is employee-owned restricted profile data. Self-service identity comes only from the authenticated session, one current contact is permitted per employee, and no administrator or category grant may redirect the self-service route to another employee. Contact values are excluded from URLs, directories, notifications, email, logs, and audit metadata. Maintenance access requires a future authorized HR workflow.
# Financial record invariants

Financial records are never silently deleted or overwritten. Property and original submitter identity come from trusted server context, remain immutable, and are not accepted from posted identity fields. Every financial correction requires a reason, authenticated actor, timestamp, stale-edit protection, and append-only history containing safe before-and-after evidence. Receipt absence does not prevent recording a transaction, but it must be explicitly declared and documented.

A financial workflow may expose multiple explicitly authorized entry points, but they must operate on one authoritative business record, receipt set, and history. Corporate users may select or correct an expense property only through a category-authorized Corporate workflow. Community Actions continues to derive its property from the authorized route context and never permits property correction.

Corporate personal-entry workspaces must not imply company-wide visibility. Authenticated submitter scope is enforced server-side for every Corporate list, summary, detail, edit, correction, and receipt route. Property-scoped operational records remain visible to authorized property users regardless of who submitted them or which authorized entry point created them.
