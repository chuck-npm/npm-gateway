# Admin category access

The Admin workspace is available only to users with effective `admin` membership. Category definitions remain in configuration, but all user membership decisions come from `user_category_access` through `CorporateAccessService`.

Migration 007 creates the table without business data. On an approved local database, `php bin/gateway category-access:backfill` resolves the active permanent users `chuck` and `tim` before mutation, then gives Chuck all five categories and Tim Finance, Human Resources, Marketing, and Credit Cards. It is transactional, idempotent, creates only missing rows, preserves unrelated memberships, and audits created grants. Hayleigh is intentionally excluded until a permanent user exists.

Fresh bootstrap grants the initial administrator all categories in the same transaction as employee and user creation. Matrix saves use public IDs, validate the complete submission, write only real changes, and audit grants and revocations. The server rejects self-removal of Admin and any result with no active Admin. Inactive users keep stored memberships but receive no effective access.

Do not edit configuration to grant access. Do not run the backfill against a normal database without explicit authorization. Rollback is intentionally refused once membership rows exist.
