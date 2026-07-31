# Gateway Constitution

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

HR employee creation is explicitly authorized through `CorporateAccessService` and is transactional across employee, primary operational assignment where applicable, Gateway user, and creation audit. Corporate context is derived without an assignment; Manager uses `property_manager`; Assistant Manager maps to employee class `manager` and assignment type `assistant_manager`. Personal contacts and Employee Notes never enter universal directory projections or routine audit metadata. Initial plaintext passwords may cross only the synchronous, post-commit secure-notification boundary and are never persisted.

The production notification transport is an injected PHPMailer SMTP adapter using authenticated TLS with platform CA verification. Unencrypted modes and an incorrect sender are rejected before employee-number allocation. Transport failures are reduced to a safe domain error; raw SMTP errors, credentials, recipients, bodies, and passwords are not logged or exposed. Because the original initial password cannot be recovered from its hash after a failed post-commit send, a future authorized reset workflow—not plaintext retry storage—is the recovery design.
