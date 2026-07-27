# NPM Gateway

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

MySQL DDL may implicitly commit, so the runner does not pretend a batch is fully
transactional. Rollback depends on every migration providing a correct
`down()` implementation. Migration files must be committed to Git with the code
that depends on them.

Do not make routine schema changes manually in MySQL Workbench. Manual database
work is limited to initial database creation, database-user and privilege
administration, and emergency inspection or repair.
