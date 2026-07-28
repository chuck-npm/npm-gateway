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
routine directory/profile reads do not create permanent audit noise.
