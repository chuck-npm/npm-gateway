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
2. Copy `.env.example` to `.env` manually and provide the application and
   migration database profiles for the managed MySQL service.
3. Configure Apache so that this project's `public/` directory is the document
   root. No other project directory should be web-accessible.
4. Open the configured local URL in a browser.

Database settings have no localhost, port, schema, credential, or certificate
defaults. Application connections use the `DB_*` variables and migrations use
the separately privileged `MIGRATION_DB_*` variables. MySQLi connections always
request TLS; when an SSL CA path is configured, the CA must be readable and
server-certificate verification is enabled.

## Current phase

This repository contains only the initial project skeleton, Composer
autoloading, safe configuration placeholders, a minimal bootstrap, placeholder
views, and a temporary front controller response. Authentication,
authorization, portal modules, and business schemas have not been implemented.
The migration infrastructure and its history table are implemented, but no
business schema or versioned migrations exist yet.

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

Check either managed database profile without exposing credentials:

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
TLS active: yes
TLS cipher: TLS_AES_256_GCM_SHA384
Database default character set: utf8mb4
Database default collation: utf8mb4_0900_ai_ci
Privilege check: CRUD present; schema changes absent
```

The application check requires `SELECT`, `INSERT`, `UPDATE`, and `DELETE` while
rejecting schema-changing grants. The migration check requires `CREATE`,
`ALTER`, and `DROP`. Live PHPUnit coverage is in the Integration suite and is
skipped unless `RUN_DB_INTEGRATION_TESTS=true`.

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

MySQL DDL may implicitly commit, so the runner does not pretend a batch is fully
transactional. Rollback depends on every migration providing a correct
`down()` implementation. Migration files must be committed to Git with the code
that depends on them.

Do not make routine schema changes manually in MySQL Workbench. Manual database
work is limited to initial database creation, database-user and privilege
administration, and emergency inspection or repair. No business tables have
been created at this phase.
