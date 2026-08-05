# NPM Gateway

Corporate Operations is authorized through the first-class `operations` category. Migration 012 expands the category constraint, and `php bin/gateway operations-access:backfill` grants the initial Operations memberships to Chuck and Tim only. Future membership is managed in Category Access. See `docs/operations-workspace.md`.

NPM Gateway is the internal company portal for NPM Properties. This repository is
a clean rebuild and contains no code from any legacy NPM Gateway or Highridge
portal.

## Requirements

- PHP 8.2 or newer
- Composer
- XAMPP on Windows for local development
- A web server configured with `public/` as the only web root

The intended production platform is IIS on Windows Server 2022.

## Local setup

1. Run `composer install` in the project root.
2. Copy `.env.example` to `.env` manually and provide both local database
   profiles.
3. Configure Apache so that this project's `public/` directory is the document
   root. No other project directory should be web-accessible.
4. Open the configured local URL in a browser.

Application connections use the `DB_*` variables and migrations use the
separately privileged `MIGRATION_DB_*` variables. No Laravel-style aliases such
as `DB_DATABASE` or `DB_USERNAME` are used.

### Local MySQL configuration

With `APP_ENV=local` or `APP_ENV=testing`, `DB_SSL_CA` and
`MIGRATION_DB_SSL_CA` may be empty only when the corresponding host is exactly
`127.0.0.1`, `localhost`, or `::1`. Those connections may run without TLS.
For example:

```dotenv
APP_ENV=local
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=npm_gateway
DB_USER=npm_gateway_app
DB_PASSWORD=
DB_SSL_CA=
MIGRATION_DB_HOST=127.0.0.1
MIGRATION_DB_PORT=3306
MIGRATION_DB_NAME=npm_gateway
MIGRATION_DB_USER=npm_gateway_migration
MIGRATION_DB_PASSWORD=
MIGRATION_DB_SSL_CA=
```

### Managed MySQL configuration

Every remote connection, and every connection outside `local` or `testing`,
requires a nonempty path to a readable CA file. This includes loopback hosts in
production. The client requests TLS, enables server-certificate verification,
and rejects the connection if no TLS cipher is negotiated.

```dotenv
APP_ENV=production
DB_HOST=managed-mysql.example.com
DB_PORT=16751
DB_NAME=npm_gateway
DB_USER=npm_gateway_app
DB_PASSWORD=
DB_SSL_CA=C:\certificates\managed-mysql-ca.pem
MIGRATION_DB_HOST=managed-mysql.example.com
MIGRATION_DB_PORT=16751
MIGRATION_DB_NAME=npm_gateway
MIGRATION_DB_USER=npm_gateway_migration
MIGRATION_DB_PASSWORD=
MIGRATION_DB_SSL_CA=C:\certificates\managed-mysql-ca.pem
```

## Current phase

The Core Platform Foundation migration creates `properties`, `employees`,
`users`, `employee_property_assignments`, and `audit_logs`. Authentication
Security adds `user_sessions` and `login_attempts` and removes the obsolete
`users.must_change_password` workflow. These migrations create no seed users,
employees, properties, assignments, sessions, attempts, or audit events.
Authorization and portal modules remain separate phases.

## Presentation foundation

The interface uses Bootstrap 5.3 components and responsive behavior. The
restrained Gateway theme in `public/assets/css/gateway.css` is loaded after
Bootstrap and provides shared colors, spacing, typography, panels, and component
overrides. Bootstrap source files are not modified, and Tailwind CSS is not
used.

During component-system development, `/component-showcase` provides a temporary
visual review page. The route returns a not-found response unless `APP_ENV` is
`local` or `development`, and it must be removed before the portal foundation
moves beyond development review.

## Directory overview

- `app/` — future namespaced application code
- `bootstrap/` — minimal application initialization
- `config/` — configuration placeholders
- `database/` — future migrations, seeders, and schema materials
- `public/` — the only web-exposed directory and browser front controller
- `resources/` — future views and source assets
- `routes/` — route definition placeholders
- `storage/` — runtime files excluded from version control
- `tests/` — PHPUnit unit and feature test suites

## Development commands

```shell
composer validate
composer install
composer dump-autoload
composer test
composer syntax-check
```

Check either local or managed database profile without exposing credentials:

```shell
php bin/gateway database:check application
php bin/gateway database:check migration
```

The command performs read-only metadata and grant checks, verifies the selected
database and `utf8mb4` settings, and exits nonzero when any expected condition
fails. It does not print credentials or connection strings. Typical successful
output has this shape (values vary by server):

```text
Profile: application
Connection: successful
Selected database: gateway
MySQL server version: 8.x.x
Connection character set: utf8mb4
TLS active: no (permitted local loopback)
TLS cipher: none
Database default character set: utf8mb4
Database default collation: utf8mb4_0900_ai_ci
Privilege check: CRUD present; schema changes absent
```

For managed connections the TLS fields instead report `yes` and the negotiated
cipher. An inactive cipher is accepted only for the local/testing loopback
policy above. Selected database, connection and schema encoding, and privilege
checks always remain mandatory. The application check requires `SELECT`,
`INSERT`, `UPDATE`, and `DELETE` while rejecting schema-changing grants. The
migration check requires `CREATE`, `ALTER`, and `DROP`. Live PHPUnit coverage is
in the Integration suite and is skipped unless `RUN_DB_INTEGRATION_TESTS=true`.

## Database migrations

The migration system provides deterministic, Git-tracked changes to the managed
MySQL schema. The normal application user is intentionally limited to CRUD. The
separately configured migration user has schema-changing privileges and is the
only profile used by migration commands.

The migration profile requires:

```text
MIGRATION_DB_HOST
MIGRATION_DB_PORT
MIGRATION_DB_NAME
MIGRATION_DB_USER
MIGRATION_DB_PASSWORD
MIGRATION_DB_SSL_CA
```

Migration files belong in `database/migrations` and use the exact filename form
`YYYYMMDDHHMM_description.php`. Descriptions start with a lowercase letter and
contain only lowercase letters, digits, and underscores. Each file returns an
anonymous object implementing `MigrationInterface`:

```php
<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(mysqli $connection): void
    {
        // Apply the schema change.
    }

    public function down(mysqli $connection): void
    {
        // Reverse the schema change.
    }
};
```

Run migrations with:

```shell
php bin/gateway migrate
php bin/gateway migrate:status
php bin/gateway migrate:rollback
php bin/gateway schema:verify
```

Apply Foundation with `php bin/gateway migrate`, inspect it with
`php bin/gateway migrate:status`, and validate it with
`php bin/gateway schema:verify`.

`migrate:rollback` is destructive and reverses the latest batch. Local
development is the approved location for destructive migration testing.
Production migrations must be tested locally—including rollback and
reapply—before production use. Foundation creates no administrator, sample
property, or other seed data. See the
[`data dictionary`](docs/data-dictionary.md) for the authoritative schema and
business rules.

Authentication is administrator-managed: users cannot choose or change
passwords, request self-service resets, or use **Remember me**. Sessions use a
60-minute idle timeout, eight-hour absolute lifetime, and periodic identifier
rotation. Five consecutive failures lock an account for 15 minutes.

Credential notices are reserved for a later secure mail service and use the
dedicated `GATEWAY_CREDENTIAL_NOTICE_*` environment settings documented in
`.env.example`.

## Bootstrap administrator

After both migrations are Ran and the business tables are empty, create the
first corporate administrator from a controlled server console:

```shell
php bin/gateway bootstrap:administrator
```

The command is interactive, displays a password once only after the database
transaction commits, and can succeed only while `users` has no rows. Gateway
stores only the password hash; a lost one-time password cannot be recovered.
Never place a password in shell arguments—the command rejects password options.

Configure `GATEWAY_CREDENTIAL_NOTICE_DRIVER`, recipient email/name, and a
subject containing `secure` before production bootstrap. No production
notifier ships in this commit, so production fails closed. Local/testing may
skip delivery only when `GATEWAY_CREDENTIAL_NOTICE_ALLOW_LOCAL_FALLBACK=true`
and both `--no-notification` and `--acknowledge-no-notification` are supplied.

Exit codes are: `0` success, `1` validation/general failure, `2` already
initialized, `3` database failure, `4` administrator committed but notification
failed, `5` initialization lock unavailable, and `6` unsafe environment or
prohibited option. A successful bootstrap creates one corporate employee, one
active user, and immutable initialization/notification audit events. It creates
no property, assignment, session, login attempt, role, or permission.

Before bootstrap, run:

```shell
php bin/gateway database:check application
php bin/gateway migrate:status
php bin/gateway schema:verify
```

## Browser authentication

Gateway provides `GET/POST /login`, protected `GET /dashboard`, and POST-only
`/logout`. Authentication failures always use the same neutral message. There
is no Remember Me, self-service password reset, account creation, MFA, or token
API in this phase.

Five consecutive known-user password failures lock the account for 15 minutes.
IP throttling defaults to ten failures in ten minutes and applies to known and
unknown usernames using a keyed IP hash. Successful login clears account
failure and lock state.

The browser receives a 32-byte URL-safe opaque token in the dedicated
`SESSION_COOKIE_NAME` cookie. Gateway stores only its
domain-separated HMAC-SHA-256 hash. Sessions have a 60-minute idle limit,
eight-hour absolute limit, five-minute activity-write threshold, and conditional
token rotation after 15 minutes. Cookies are HttpOnly, SameSite=Lax, path `/`,
non-persistent, and Secure in production.

Configure `APP_KEY` with at least 32 characters and the `SESSION_*` and
`AUTH_*` settings in `.env.example`. Production fails closed unless
`SESSION_SECURE=true`.

Native PHP session state is separate and uses
`NATIVE_SESSION_COOKIE_NAME` (default `npm_gateway_ui_state`) only for CSRF,
flash messages, and temporary UI state. It must never equal the Gateway
authentication cookie name.

Manual validation after explicitly bootstrapping the normal local database:

1. Run `php bin/gateway bootstrap:administrator`.
2. Open `http://npm-gateway.local/login`.
3. Sign in with the one-time credentials and confirm `/dashboard`.
4. Submit the dashboard logout form and confirm return to `/login`.

## Dashboard foundation

`GET /dashboard` uses the permanent authenticated shell: a skip link, dark
top navigation, page header, responsive content grid, authenticated user menu,
and footer. Only implemented routes appear in navigation; Gateway does not use
a permanent sidebar.

Dashboard totals are read-only facts supplied by `DashboardSummaryService`.
When no properties exist, the page presents non-interactive upcoming setup
tasks. When properties exist, it shows a neutral ready state. Charts, trends,
activity, and operational metrics are not shown unless backed by implemented,
verified data.

For local browser validation, sign in, confirm the Dashboard navigation state
and real counts at desktop and mobile widths, then use the CSRF-protected Sign
out form. The native UI-state and Gateway authentication cookies must remain
distinct.

MySQL DDL may implicitly commit, so the runner does not pretend a batch is fully
transactional. Rollback depends on every migration providing a correct
`down()` implementation. Migration files must be committed to Git with the code
that depends on them.

Do not make routine schema changes manually in MySQL Workbench. Manual database
work is limited to initial database creation, database-user and privilege
administration, and emergency inspection or repair.
# Operations home page

The authenticated `/dashboard` route is the permanent Gateway operations home
page. It leads with a compact welcome banner whose display name, employee class,
and job title come from the validated database-backed identity. Universal Tools
is the primary content: a deterministic catalog of 12 everyday capabilities
covering Company Directory, Property Information, Company Documents,
Announcements, Credit Card Purchases, Large File Transfers, Order Supplies,
Time Off Requests, Policies & Procedures, Training Library, Support Requests,
and Help Desk.

`DashboardHomeService` composes the existing truthful `DashboardSummaryService`
with the database-independent `UniversalToolProviderInterface`. Typed,
immutable `ToolCard` values are rendered by reusable tool-section and tool-card
components. A capability is an accessible link only when its approved internal
route is real. Planned capabilities render as readable non-interactive cards:
there are no `#` placeholders, generic stub pages, or dead links.

The former setup dashboard remains as a secondary system-status panel with real
counts. The page uses no sidebar, fake activity, chart, client-side filtering,
or custom JavaScript. At 1280px and above the grid has four columns, stepping
through three, two, and one column for narrower screens.

Manual validation: sign in at `http://npm-gateway.local`, open `/dashboard`,
verify the identity context and 12 planned cards, resize through 320, 768, 1280,
and 1920 pixels, confirm no horizontal scrolling or clickable planned card,
then verify the keyboard-operated user menu, refresh persistence, and POST
logout. Do not alter business data during this check.

Corporate employees also receive a Corporate Tools section between Universal
Tools and System Status. Its four priority areas are Finance, Human Resources,
Marketing, and Admin. The top navigation presents the same broad areas in a
Corporate dropdown. Human Resources is enabled for explicitly authorized users;
the remaining unimplemented cards and dropdown entries are readable but
non-interactive and expose no destination.

Corporate visibility is determined by the Gateway corporate-access framework
through an injected access service. The authenticated user's permanent,
non-reused Gateway username is matched against username membership in
`config/corporate-access.php`; the configured lists are not exposed in the
dashboard result or HTML. Business and personal email, shared property
mailboxes, employee classification, job title, and property assignment do not
grant application access. Commit 008 uses this decision for presentation
filtering; final module authorization still belongs in application services
and middleware. During manual validation,
confirm the section order, the enabled Human Resources destination, disabled planned entries, and
mobile navbar behavior at 320, 768, 1280, and 1920 pixels.

## Company Directory

The Company Directory card is Gateway's first enabled Universal Tool. Every
authenticated user may open the read-only workspace at `/employees`.
Server-side GET criteria provide
bounded search, class/status filters, whitelisted sorting, and pagination.
The user-facing experience is search-first because its primary purpose is to
find employee company contact information quickly.

Only approved operational fields are presented: employee number, name, title,
class, status, business contact information, current assignment summary, and
Gateway access status. Personal contact data, database IDs, passwords,
authentication internals, historical assignments, and HR-only information are
excluded. Gateway access is Active, Inactive, or None from the linked user; it
is never inferred from class. Employees, including maintenance employees, may
correctly have no Gateway user.

Directory read access grants no write permission. No create, edit, disable,
import, or export routes exist. For manual validation, search by name and
employee number, apply and clear filters, and repeat at 320, 768, 1280, and
1920 pixels.

The Company Directory boundary is universal approved contact information only.
It has no employee-detail route or View action. Complete employee records,
detailed assignments, employment history, documents, notes, and dates belong
exclusively to the restricted Human Resources module, which is not implemented
in this commit.

Future employee creation remains deliberately deferred. Corporate-authorized
users will create corporate employees, managers, and assistant managers;
managers and assistant managers automatically receive Gateway accounts.
Managers and assistant managers may create active maintenance employees within
their approved operational context. Maintenance employees never receive
Gateway accounts and appear immediately in the directory. Corporate employees
will receive an approved contact-information notice for each new maintenance
employee.

Gateway will generate permanent, never-reused employee numbers in `NPM######`
format. Creation must use a bounded MySQL advisory lock and transaction,
recheck uniqueness, and fail safely after `NPM999999`; an unlocked
`MAX + 1` implementation is prohibited. Transfers close the current assignment
and create a new assignment while preserving the same employee and assignment
history. Corporate is a valid future operational context, but no synthetic
Corporate property or assignment is created here. Employee photos, avatars,
photo uploads, and image placeholders are outside the employee module.

## Properties and Human Resources

Every authenticated Gateway user can open the read-only `/properties`
directory. It presents PropID, name, one copy-friendly formatted address,
office phone, IVR phone, and the active assigned manager. Human Resources is
separately enforced through permanent-username membership in
`config/corporate-access.php`; class, title, email, and assignments do not grant
access. Its landing page contains Employees, Properties, and a disabled Credit
Cards card—no Appliances card.

Authorized HR users can create properties at `/human-resources/properties`.
PropID, the permanent two-letter NPM code, and the lowercase URL slug are
distinct immutable identifiers manually preserved during initial population.
Automatic PropID allocation under the future 200-number spacing policy is not
implemented. Structured addresses remain structured in the database, while
both directories share one complete escaped display value. Manager names come
only from active assignments and display `Not assigned` otherwise. Property
editing, deletion, manager assignment changes, employee creation, and credit
card workflows remain deferred.

Corporate is seeded as Gateway's foundational operational context using the
normal property model (PropID 1, code `CO`, slug `corporate`). It appears in
both property directories, cannot be created through HR property administration,
and intentionally has no IVR. The idempotent local-only seeder is
`php bin/gateway seed:corporate-context`; it fails closed on identifier conflicts
and is also invoked inside future administrator initialization transactions.

Authorized property creation uses the dedicated
`/human-resources/properties/create` page. Major entity workflows use normal
page context and scrolling; Bootstrap modals are reserved for smaller bounded
actions. Validation failures return to the create page with safe values and
field errors, while successful creation redirects to the HR property directory.
### Human Resources employee administration

Authorized Corporate users manage employees at `/human-resources/employees` and create them on a dedicated `/human-resources/employees/create` page. New HR-created employees require `start_date`, restricted `date_of_birth`, company contact details, operational context, and a permanent lowercase username. Personal phone, personal email, and plain-text Employee Notes are optional and persist as `NULL` when absent. Date of Birth, personal contacts, and notes remain HR-restricted and are excluded from universal directory projections and routine audit metadata. The mandatory SECURE notification includes every field and displays `Not provided` for absent optional values.

The `employees.date_of_birth` column is nullable for legacy compatibility and is never used to store age. Future birthday reminders may derive month and day for active employees only, without exposing the birth year or age outside HR; no separate birthday column, reminder, query, scheduled task, or dashboard feature is implemented.

Every Corporate, Manager, and Assistant Manager created here receives a Gateway account. Employee numbers use locked, never-reused `NPM######` allocation. Gateway generates and Argon2id-hashes the initial permanent password; plaintext exists only in process memory through the synchronous post-commit `SECURE` notification attempt. Configure comma-separated recipients with `HR_NEW_EMPLOYEE_NOTIFICATION_RECIPIENTS` and the approved sender with `MAIL_FROM_ADDRESS=no-reply@npmpropertiesinc.com`. A failed notification does not roll back committed employee data, and the unrecoverable plaintext password is never stored for retry. Password reset/change workflows remain deferred.

New-employee mail uses PHPMailer over authenticated TLS (`SMTP_SECURE=tls`) or implicit TLS (`SMTP_SECURE=ssl`) with normal certificate verification. `php bin/gateway notification:check` performs a local-only, non-sending configuration diagnostic and reports no credentials. It never opens an SMTP connection or sends an email. Before enabling 100 MiB Company Notice uploads, run `php bin/gateway upload:check` in the deployed web runtime and separately verify the effective IIS request-filtering limit; the command reports effective PHP limits without changing them or exposing configuration paths.
# Category access administration

Corporate category definitions live in `config/corporate-access.php`; user memberships do not. Durable memberships are stored in `user_category_access` and are managed at `/admin/category-access`. Migration 007 creates schema only. Existing local users are initialized separately with `php bin/gateway category-access:backfill`, which grants Chuck all five approved categories and Tim every category except Admin.

Fresh bootstrap administrators receive all five memberships in the bootstrap transaction. An inactive user retains membership records but has no effective category access. Administrators cannot remove their own Admin membership or leave the system without an active Admin. Grant, revoke, matrix-change, and backfill operations are audited. Do not restore username membership lists to configuration.
# Notifications

Gateway provides global authenticated notifications with durable per-user acknowledgment, truthful outstanding counts, materialized recipient history, and audited delivery status. See [Notifications architecture](docs/architecture/NOTIFICATIONS.md). Migration 008 must be validated against `npmgateway_test` before explicit approval to apply it to the normal local database.

All Gateway notification audiences use one platform eligibility policy. New recipients must be active Corporate or Manager employees with an active Gateway user; Maintenance and unknown employee classes are excluded before recipient rows, email grouping, acknowledgment requirements, and reporting. This code-level rule is not configurable through `.env` or Category Access.

Authenticated Corporate and Manager employees can maintain one restricted emergency contact from the user menu at `/my/emergency-contact`. Identity is derived exclusively from the authenticated session, responses are private/no-store, and saving ECI produces sanitized audits only—no email or Gateway notification. See [Profile self-service](docs/profile-self-service.md) and [Employee privacy](docs/employee-privacy.md).

Users with effective Human Resources access can review active-employee ECI completion and maintain the same records—including Maintenance ECI—through `/corporate/human-resources/emergency-contacts`. Fixed completion reminders are email-only, centrally restricted to eligible Corporate and Manager employees, and limited to one successful reminder per employee per 24 hours.

Authorized Corporate users publish manual communications through **Company Notices**; recipients read them in **Notifications**. Version 1 uses a fixed All Active Gateway Users audience, review-before-publish, immutable publication, optional acknowledgment, and the shared company-announcement email renderer. Draft persistence and targeted audiences are planned, not implemented.

Property assignment records where an employee works and remains employment history. Database-backed **Property Access** separately authorizes which active communities an active Gateway user may open in Community Actions. Administrators manage explicit grants at `/corporate/admin/property-access`; routine floating-manager changes require no code or configuration edit. See [Property Access and Community Actions](docs/property-access.md).

Community Actions uses a property-first workspace: users select an explicitly authorized active property before seeing the ten planned action categories. Every property and action request re-resolves the route slug and current grant into a trusted server-side context; future forms inherit user identity from the session and property identity from that context and may not submit either identity.

Application Reviews is the first implemented Community Action and an independent Corporate category. Managers submit from an authorized property workspace; explicitly authorized Corporate reviewers use `/corporate/application-reviews` as the centralized cross-property queue over the same records and append-only history. Neither Operations nor Property Access grants this Corporate function. Initial outbound mail is fail-closed test routing only: set `APPLICATION_REVIEW_TEST_MODE=true`, `APPLICATION_REVIEW_TEST_EMAIL` to the explicitly approved test address, and `APP_URL` for links. Submission email links target `/corporate/application-reviews/{reviewPublicId}`; manager decision links remain property-scoped. Production recipient routing remains disabled.
