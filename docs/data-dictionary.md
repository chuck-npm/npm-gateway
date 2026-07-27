# NPM Gateway Data Dictionary

Version 1.0 — Foundation migration `202607270001_foundation`.

> **No migration may modify an existing business table unless the data dictionary is updated as part of the same change.**

## Approved principles

The schema separates permanent property identity, retained employee history, authentication identity, historical assignments, and immutable audit history. Public IDs are unique 26-character ULID-style values; internal IDs are unsigned auto-incrementing BIGINTs. Business tables use InnoDB, `utf8mb4`, `utf8mb4_0900_ai_ci`, DATETIME application timestamps, named constraints, and RESTRICT—not cascading—core relationships. Stable controlled values use VARCHAR and CHECK constraints. Application services enforce cross-row rules that SQL cannot reliably express.

## `properties`

Permanent property identity and operational contacts; operational properties are not deleted.

| Columns | Meaning |
|---|---|
| `id`, `public_id` | Internal key and stable public ID. |
| `property_code`, `slug` | Immutable two-uppercase-letter code and lowercase URL/application ID. |
| `display_name`, `legal_name`, `status` | Current and optional legal names; active/inactive/sold/archived state. |
| `manager_email`, `ivr_number`, `website_url` | The property-owned reusable manager mailbox, permanent advertising/IVR number, and optional site. |
| `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `timezone` | Address and IANA timezone; state is two uppercase letters. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional user actors. |

Unique indexes: public ID, property code, slug, manager email, IVR number. Other indexes: status and state/city. Checks: code, slug, status, state, lowercase manager email. Audit actors reference users with RESTRICT. Do not add a generic main phone: IVR belongs to the property; a routed company phone belongs to an employee.

## `employees`

Retained corporate, manager/assistant-manager, and maintenance history. Employees are disabled or terminated, not deleted.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `employee_number` | Internal/public IDs and permanent application-generated `NPM` plus six digits. |
| `employee_class` | corporate, manager, or maintenance. |
| `first_name`, `middle_name`, `last_name`, `preferred_name` | Person names. |
| `business_email`, `personal_email`, `company_phone`, `personal_phone` | Optional contact channels. |
| `job_title`, `employment_status` | Title and active/leave/inactive/terminated state. |
| `hire_date`, `termination_date`, `supervisor_employee_id` | Employment dates and optional employee supervisor. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional user actors. |

Unique indexes: public ID, employee number, nullable business email. Other indexes: name, class/status, supervisor, hire date. Checks: number, class, status, lowercase emails, valid termination order. Foreign keys: supervisor self-reference and user audit actors, all RESTRICT.

The application supplies and never reuses employee numbers; no trigger or `MAX()` derivation is permitted. Maintenance employees cannot have users or company phones. Managers and assistant managers are expected to have company phones; corporate staff may have them. Manager working email normally derives from the active primary property's manager mailbox.

## `users`

Gateway authentication identities only; no HR/property contact data belongs here.

| Columns | Meaning |
|---|---|
| `id`, `public_id`, `employee_id` | Internal/public IDs and required unique employee owner. |
| `username`, `password_hash`, `status` | Permanent login, PHP password hash, and pending/active/locked/disabled state. |
| `must_change_password`, `password_changed_at`, `password_reset_at` | Password lifecycle. |
| `last_login_at`, `failed_login_count`, `locked_until`, `disabled_at` | Login, lockout, and disabling state. |
| `created_at`, `updated_at`, `created_by`, `updated_by` | Timestamps and optional self-referencing user actors. |

Unique indexes: public ID, employee ID, username. Other indexes: status and lock expiry. Checks: lowercase username, pattern `^[a-z][a-z0-9]{1,49}$`, status, boolean password-change flag, consistent disabled state. Employee and audit foreign keys use RESTRICT.

An employee has zero or one user; maintenance employees have none. Usernames normally begin with the first name, with duplicates such as `john2`; they are normalized lowercase, permanent, and never reused. Accounts are disabled, never deleted. There is no self-service recovery. Authorized administrators reset passwords, invalidate active sessions, and set `must_change_password = 1`. Plaintext passwords are forbidden.

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
