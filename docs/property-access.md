# Property Access and Community Actions

Property assignment answers where an employee currently works and remains authoritative employment history in `employee_property_assignments`. Property Access answers which communities an authenticated Gateway user may open and work within. Neither system mutates or implies the other.

Administrators with effective `admin` Category Access manage Property Access at `/corporate/admin/property-access`. The matrix shows every employee and active property, orders properties by full display name, uses the stored two-letter `property_code` as its compact heading, and exposes the full name accessibly. Corporate and Manager employees with active Gateway accounts are assignable. Maintenance, inactive employees, employees without a Gateway user, and users with inactive accounts remain visible but cannot receive effective access.

Community Actions derives its alphabetized cards only from current `user_property_access` rows joined to active users and active properties. Numeric IDs never appear in routes; immutable property slugs identify workspaces. No grant produces the standard no-access empty state. Unknown slugs return 404, while a known active property without an effective grant returns 403.

The legacy `config/community-access.php` file is deprecated and is not an authorization source. Routine floating-manager grants and revocations are database-backed, transactional, immediately effective, sanitized in audits, and never modify assignment history.

Property selection always precedes action selection. Every property workspace and action page is resolved through `CommunityActionContextResolver`, which combines the authenticated session identity with the active property resolved from the route slug and a current `PropertyAccessService` check. Controllers and future workflows receive this trusted context; they do not independently interpret submitted identities. Revocation therefore takes effect on the next request, including a bookmarked action URL.

Community Action forms must never render or accept selectors or hidden fields for property, property public ID, property slug, user, employee, manager, or submitter identity. Future writes assign `submitted_by_user_id` from the session and `property_id` from the resolved route context server-side, recheck authorization before validation, and reject posted identity overrides.

The initial planned action catalog, in its authoritative display order, is: Application Reviews; Credit Card Purchases; RM Corrections; Renovation Request; Request Appliances; Appliance Distribution; HVAC Service Request; Order Supplies; Eviction Checks; and RM Audit. RM means Rent Manager and remains established NPM terminology. The centralized `CommunityActionProvider` supplies card, route, placeholder, and future navigation metadata.

## Route and access matrix

| Route | Required access |
|---|---|
| `/community-actions` | Authenticated Gateway user |
| `/community-actions/{propertySlug}` | Authentication plus effective access to that active property |
| `/community-actions/{propertySlug}/{approvedActionSegment}` | Authentication plus freshly resolved effective access to that active property |
| `/corporate/admin/property-access` | Effective `admin` Category Access |
