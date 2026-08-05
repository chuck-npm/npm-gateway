# Community Actions

Community Actions is property-first and property-scoped. `/community-actions` lists only active properties with an explicit effective Property Access grant. Selecting a property opens `/community-actions/{propertySlug}`; action cards never appear before this selection.

`CommunityActionContextResolver` resolves the authenticated user, active property slug, and current explicit grant on every property workspace and action request. Known active properties without a grant return 403. Unknown and inactive properties return 404. Responses are private and not stored in shared caches. Property Access changes workspace authority only and never changes `employee_property_assignments`.

`CommunityActionProvider` is the single catalog, in order: Application Reviews, Credit Card Purchases, RM Corrections, Renovation Request, Request Appliances, Appliance Distribution, HVAC Service Request, Order Supplies, Eviction Checks, and RM Audit. Application Reviews is implemented; the remaining nine modules are planned. RM means Rent Manager and is not expanded in interface labels.

All action URLs retain the authorized property slug. Placeholder pages repeat the property in their breadcrumb, page-header context, and content. No sidebar, form, table, workflow persistence, approval, notification, email, or external storage operation is part of this foundation.

Application Reviews demonstrates the form contract: the submitter comes from the authenticated session and the property from the resolved route context. Forms contain no visible or hidden identity fields for a property, user, employee, manager, reviewer, sender, or recipient. POST authorization is rechecked before business validation. See [Application Reviews workflow](application-reviews.md).
