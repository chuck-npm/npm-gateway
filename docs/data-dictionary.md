# NPM Gateway Data Dictionary

Version 1.2 — Foundation through Employee Date of Birth migration `202608010006_employee_date_of_birth`.

> **No migration may modify an existing business table unless the data dictionary is updated as part of the same change.**

## Approved principles

The schema separates permanent property identity, retained employee history, authentication identity, historical assignments, and immutable audit history. Public IDs are unique 26-character ULID-style values; internal IDs are unsigned auto-incrementing BIGINTs. Business tables use InnoDB, `utf8mb4`, `utf8mb4_0900_ai_ci`, DATETIME application timestamps, named constraints, and RESTRICT—not cascading—core relationships. Stable controlled values use VARCHAR and CHECK constraints. Application services enforce cross-row rules that SQL cannot reliably express.

## `properties`

Permanent property identity and operational contacts; operational properties are not deleted.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `prop_id` | Internal relational key, stable non-sequential public ID, and permanent numeric NPM business identifier. |
| `property_code`, `slug` | Permanent two-uppercase-letter NPM operational code and lowercase URL-safe identifier. |
| `display_name`, `legal_name`, `status` | Current and optional legal names; active/inactive/sold/archived state. |
| `office_phone`, `manager_email`, `ivr_number`, `ivr_routing_email`, `website_url` | Community office phone, property-owned manager mailbox, advertising/IVR number, IVR routing mailbox, and optional site. |
| `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `timezone` | Address and IANA timezone; state is two uppercase letters. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional user actors. |

Unique indexes: public ID, PropID, property code, slug, manager email, IVR number. Other indexes: status and state/city. Checks include positive PropID, code, slug, status, state, and lowercase operational mailboxes. Audit actors reference users with RESTRICT. An ambiguous generic `phone` field remains prohibited: `office_phone` is specifically the community office number, `ivr_number` is the advertising/IVR number, `ivr_routing_email` receives IVR routing messages, and `manager_email` is the property business mailbox.

The five identifiers are distinct and cannot substitute for one another: `id` is the relational database key; `public_id` is the stable non-sequential public identifier; `prop_id` is the permanent numeric NPM business identifier; `property_code` is the permanent two-character operational code; and `slug` is the permanent URL-safe identifier. During controlled legacy population, operators manually preserve the actual PropID, property code, and slug. They are assigned once, never reused, and do not change when a property is renamed. Future automatic PropID allocation using the approved 200-number spacing policy is deferred.

Corporate is Gateway's permanent foundational operational context in `properties`: PropID `1`, property code `CO`, and slug `corporate`. These identities are reserved from ordinary property administration and Corporate cannot be deleted or made inactive through routine workflows. Its approved address is `5021 River Rd., Ste. C, Columbus, GA 31904`; its operational mailbox does not represent an employee manager. Corporate intentionally has no advertising IVR, so `ivr_number` and `ivr_routing_email` are null only for this protected context. Community properties continue to require both values. Corporate is created idempotently during initialization or an approved local backfill, with exact three-identifier conflict detection and no silent overwrites.

## `employees`

Retained corporate, manager/assistant-manager, and maintenance history. Employees are disabled or terminated, not deleted.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `employee_number` | Internal/public IDs and permanent application-generated `NPM` plus six digits. |
| `employee_class` | corporate, manager, or maintenance. |
| `first_name`, `middle_name`, `last_name`, `preferred_name` | Person names. |
| `date_of_birth` | Nullable `DATE` containing restricted HR Date of Birth data. Legacy and imported rows may remain null; the HR Add Employee workflow requires an ISO calendar date for new records. Age is never stored. |
| `business_email`, `personal_email`, `company_phone`, `personal_phone` | Optional contact channels. |
| `job_title`, `employment_status` | Title and active/leave/inactive/terminated state. |
| `start_date`, `termination_date`, `supervisor_employee_id` | Required NPM employment start date (which may predate the Gateway record), termination date, and optional employee supervisor. `start_date` replaces the former `hire_date` terminology. |
| `comments` | Nullable plain-text internal Employee Notes. Blank Add Employee submissions persist as null. HR-restricted and excluded from universal directories, logs, and ordinary audit metadata. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional user actors. |

Unique indexes: public ID, employee number, nullable business email. Other indexes: name, class/status, supervisor, hire date. Checks: number, class, status, lowercase emails, valid termination order. Foreign keys: supervisor self-reference and user audit actors, all RESTRICT.

Assistant Manager is represented by `employee_class = 'manager'` and an active primary `assignment_type = 'assistant_manager'`; no assistant-manager employee class exists. Corporate employees have no assignment row. Their Corporate operational context is centrally derived from `employee_class = 'corporate'` and the protected Corporate property identity.

New-employee credential notification is synchronous after the employee transaction commits. Recipient configuration is external, the sender must resolve to `no-reply@npmpropertiesinc.com`, and transport requires authenticated TLS. Notification content is never stored in business or audit tables; only a safe sent/failed status is audited.

The application generates and never reuses employee numbers. Allocation
uses a bounded MySQL advisory lock and employee-creation transaction, reads the
highest recorded six-digit suffix, rechecks uniqueness, inserts, and releases
the lock in `finally`. An unlocked `MAX() + 1` operation is prohibited, and
`NPM999999` exhaustion fails safely pending an approved policy revision.
Maintenance employees cannot have users or company phones. Managers and
assistant managers automatically receive Gateway users and are expected to
have company phones; corporate staff may have them. Manager working email
normally derives from the active primary property's manager mailbox.

The universal Company Directory exposes only approved contact and operational
summary fields. Complete employee records, dates, history, detailed
assignments, notes, documents, and personnel workflows belong to restricted
Human Resources services and are not exposed through a universal detail route.

Date of Birth, personal phone, personal email, and Employee Notes are restricted HR data and never enter universal or HR directory tables, general search, URLs, logs, success messages, or ordinary audit metadata. Birthday reminders are a future feature derived from month/day for active employees only; general reminders must not expose birth year or age, and no age or separate birthday field is stored.

## `users`

Gateway authentication identities only; no HR/property contact data belongs here.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `employee_id` | Internal/public IDs and required unique employee owner. |
| `username`, `password_hash`, `status` | Permanent login, PHP password hash, and pending/active/locked/disabled state. |
| `password_changed_at`, `password_reset_at` | Administrator-managed password lifecycle. |
| `last_login_at`, `failed_login_count`, `locked_until`, `disabled_at` | Login, lockout, and disabling state. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional self-referencing user actors. |

Unique indexes: public ID, employee ID, username. Other indexes: status and lock expiry. Checks: lowercase username, pattern `^[a-z][a-z0-9]{1,49}$`, status, and consistent disabled state. Employee and audit foreign keys use RESTRICT. Authentication Security removed `must_change_password`; Gateway has no forced password-change workflow.

An employee has zero or one user; maintenance employees have none. Usernames normally begin with the first name, with duplicates such as `john2`; they are normalized lowercase, permanent, and never reused. Accounts are disabled, never deleted. There is no self-service recovery and users cannot select initial passwords, change passwords, or request resets. Authorized administrators create and replace passwords and revoke all active sessions on replacement. Plaintext passwords are forbidden.

## `employee_property_assignments`

Current and historical property assignments.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `employee_id`, `property_id` | Identity and assigned employee/property. |
| `assignment_type`, `is_primary` | property_manager, assistant_manager, floating_manager, maintenance, temporary_coverage, or regional_support; primary flag. |
| `starts_on`, `ends_on`, `notes` | Effective range (null end is active) and optional context. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional user actors. |

Public ID is unique. Indexes support active employee/property, employee-primary, property-type, and date-range queries. Checks validate type, boolean primary, and date order. Employee, property, and audit foreign keys use RESTRICT.

Corporate employees cannot receive assignments. Managers and floating managers have exactly one active primary and may have secondary assignments; assistant managers may have a primary; maintenance staff may be assigned. The application enforces one active primary transactionally. History is ended, not overwritten/deleted. Assignment does not grant Gateway community access; access stays explicit in `config/community-access.php`.

A property's authoritative directory manager is its sole assignment with `assignment_type = 'property_manager'`, `is_primary = 1`, a null `ends_on`, and an active employee. Generated nullable keys and unique indexes enforce at most one such assignment per property and at most one per employee. Non-primary manager assignments represent supporting, floating, temporary, or secondary responsibility and never determine the directory Manager value. Each manager may have only one active primary property assignment.

## `audit_logs`

Append-only operational/security history.

| Columns | Meaning |
|---|---|
| `id`, `public_id` | Internal and unique public IDs. |
| `user_id`, `employee_id`, `property_id` | Optional account actor, retained person, and related property. |
| `event_type`, `entity_type`, `entity_id`, `entity_public_id` | Stable event and optional non-polymorphic entity reference. |
| `description`, `before_data`, `after_data` | Summary and optional redacted JSON snapshots. |
| `ip_hash`, `user_agent`, `created_at` | Optional lowercase SHA-256-style IP hash, client agent, and event time. |

Indexes cover time; user/employee/property/event plus time; and entity internal/public identity. Actor/property foreign keys use RESTRICT; the IP hash has a named format check. There are intentionally no update or audit-actor columns.

Audit rows are never updated or normally deleted. Sensitive values must be redacted before insertion. Passwords/hashes, temporary credentials, sessions, raw tokens, SMTP credentials, and database credentials are forbidden. System events may have null user IDs. Event types will be governed by application constants/value objects.

## `user_sessions`

Tracks authenticated sessions for validation, expiration, rotation, and immediate revocation. Raw session identifiers are never persisted; revoked and expired records are temporarily retained for security history.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `user_id` | Internal/public IDs and required user owner. |
| `session_token_hash` | Unique keyed SHA-256 or equivalent hash; never a raw token. |
| `ip_hash`, `user_agent` | Optional privacy-preserving client identity. |
| `last_activity_at`, `idle_expires_at`, `absolute_expires_at`, `rotated_at` | Activity, 60-minute idle expiry, eight-hour hard expiry, and last rotation. |
| `revoked_at`, `revoked_by`, `revocation_reason` | Revocation state and responsible account. |
| `created_at` | Session creation time. |

Unique indexes cover public ID and token hash. Other indexes support active sessions and expiry/revocation cleanup. User and revoker foreign keys use RESTRICT. Checks enforce lowercase 64-character hashes, expiration and rotation ordering, approved reasons, and consistent revocation state. There are no generic update or audit-actor columns.

PHP session IDs and database tokens use cryptographically secure generation. Only an HMAC/keyed hash using `APP_KEY` or a derived secret is stored. Raw tokens must never enter logs, email, audit JSON, or persistent storage. Validation requires an active account, an unrevoked session, and unexpired idle and absolute limits. Controlled services update activity (possibly throttled), rotate immediately after login and approximately every 15 minutes, revoke at logout, and revoke all sessions after password replacement or account disablement. There is no persistent login or **Remember me**. Cleanup will use a later approved maintenance command; active sessions are never physically deleted.

## `login_attempts`

Immutable successful and failed authentication attempts for lockout, throttling, investigation, and audit support. Guessed usernames are deliberately not stored in plaintext.

| Columns | Meaning |
|---|---|
| `id`, `public_id` | Internal and unique public IDs. |
| `submitted_username_hash`, `user_id` | Keyed HMAC of the normalized lowercase submission and optional matched user. |
| `was_successful`, `failure_reason` | Result and approved internal reason. |
| `ip_hash`, `user_agent`, `attempted_at` | Optional client identity and attempt time. |

Indexes cover public ID, submitted hash/time, user/time, IP/time, result/time, and attempt time. The optional user foreign key uses RESTRICT. Checks enforce hash formats, boolean results, approved failure reasons, and success/failure consistency. There are no generic update or audit-actor columns.

Every attempt is recorded. Unknown usernames have null user IDs; known users may still record invalid credentials, disabled, locked, or rate-limited outcomes. User-visible failures remain neutral. Five consecutive failures lock the account for 15 minutes; successful authentication clears the counter and lock. IP throttling is applied by application services, initially suggested as ten failures in ten minutes. Exact limits belong in configuration. Retention and cleanup will be established later.

## Authentication and credential delivery policy

Gateway-generated passwords use `random_bytes()` or an equivalent cryptographically secure source and at least 24 characters of entropy. Easily confused characters may be avoided. Hash with `PASSWORD_ARGON2ID` when operationally available, otherwise `PASSWORD_DEFAULT` with the runtime algorithm documented. A plaintext password may be displayed once to an authorized administrator, then its variable is cleared after hashing, display, and approved delivery. It must never appear in logs, exceptions, tests, audit events, or command history.

Administrator password replacement updates the hash and password timestamps, clears failure and lock state, revokes active sessions, and creates an audit event. High-value events such as successful login, lock, logout, reset, disable/enable, and session revocation use `audit_logs`; detailed rejected attempts belong in `login_attempts`.

Future credential notices use the dedicated `GATEWAY_CREDENTIAL_NOTICE_*` settings and subject `secure - NPM Gateway User Credentials`. The body may include approved employee, account, property, phone, and administrator context, but is never logged. Production mail debugging is disabled. Delivery failure must be reported safely, and provisioning must either roll back or provide a controlled one-time fallback rather than silently succeed.

> **Email archives containing current credentials are highly sensitive and must be protected by the organization's encrypted mail controls, administrative access restrictions, and retention policy.**

## Application ownership and bootstrap administration

Employee creation is owned by `EmployeeService`, user creation by `UserService`,
and immutable audit creation by `AuditService`. `SystemInitializationService`
is the sole coordinator for initial-administrator bootstrap and the sole owner
of its transaction. CLI commands, controllers, imports, jobs, future APIs, and
AI clients must use these services and never insert business rows directly.

Bootstrap is allowed exactly once: any `users` row, including a disabled one,
means Gateway is initialized. It creates one active corporate employee and one
active user with a permanent normalized username. It creates no property or
property assignment for the corporate administrator and no session or login
attempt. The generated password is shown once, never stored as plaintext, and
only its PHP password hash is persisted.

The atomic transaction inserts employee, user, and
`system.administrator_initialized` audit rows. Credential delivery occurs after
commit so transport latency or failure cannot leave partial business data.
Delivery produces an immutable sent, failed, or explicitly skipped-local audit
event. On failure the administrator remains committed, the operator receives a
distinct result, and the one-time password must be handled securely. It cannot
be resent because Gateway intentionally retains no recoverable password.

## Authentication runtime rules

Usernames are trimmed and lowercased before lookup. Passwords are verified with
`password_verify`; successful verification may opportunistically replace an
outdated hash. Unknown and invalid usernames are checked against a runtime
dummy hash to reduce timing differences. Browser errors remain neutral.

`users.failed_login_count` is the consecutive applicable known-user password
failure count. The fifth failure sets `locked_until` 15 minutes ahead. Unknown,
disabled, already locked, and IP-rate-limited attempts do not arbitrarily
increment it. Successful login resets the count and clears `locked_until`.

Every authentication attempt creates one immutable `login_attempts` row.
Submitted usernames and IP addresses use separately domain-separated keyed
hashes; raw values are not stored. The initial IP policy is ten failures in ten
minutes.

`user_sessions` stores only a domain-separated HMAC-SHA-256 token hash. The raw
32-byte URL-safe token exists only in the browser cookie and controlled return
objects. Idle expiry is 60 minutes, absolute expiry eight hours, and rotation is
due after 15 minutes. Activity writes are throttled to five minutes and idle
expiry is capped by absolute expiry. Rotation conditionally replaces the old
hash, so one concurrent request wins and stale requests reauthenticate.

Revocation preserves rows with approved reasons including `logout`,
`idle_expired`, `absolute_expired`, and `account_disabled`. Successful login
creates `authentication.login_succeeded`; lockout creates
`authentication.account_locked`; logout creates `authentication.logout` and
`authentication.session_revoked`. Audit metadata never contains passwords,
password hashes, raw tokens, or token hashes.
# `user_category_access`

Durable per-user Corporate category authorization introduced by Migration 007. `user_id` identifies the member; `category` is one of `finance`, `human-resources`, `marketing`, `admin`, or `credit-cards`. The pair is unique. `public_id` is the external identifier, while `granted_by_user_id`/`granted_at` and nullable update fields retain provenance. All user foreign keys use `RESTRICT`; application services supply public IDs and timestamps. Membership remains stored for inactive users, but is ineffective while the linked user is inactive.
