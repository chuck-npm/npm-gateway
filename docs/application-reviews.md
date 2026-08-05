# Application Reviews workflow

Application Reviews uses one durable business record for two authorization views. Managers work under `/community-actions/{propertySlug}/application-reviews`, where every GET and POST resolves a fresh `CommunityActionContext` and current Property Access. Corporate reviewers work under `/corporate/application-reviews` using explicit effective `application-reviews` category access across all properties; they do not require Property Access.

Managers submit a prospect name, optional plain-text comments, and the required affirmation that the application and all supporting documents are already in Rent Manager. Gateway stores no application documents. User and property identities are assigned server-side, initial status is `pending_review`, and the manager cannot edit or delete the submission.

Corporate reviewers approve or deny with required plain-text comments. The decision button supplies an allowlisted action; reviewer identity and timestamp come from the authenticated session and company clock. The service locks and rechecks the pending record, updates exactly once, and appends matching immutable history. Completed decisions cannot be reopened or overwritten in Version 1.

The business transaction creates the review/history and sanitized platform audit before commit. Email is attempted only after commit, so configuration or SMTP failure preserves the record and history. Audits contain public review/property/actor IDs, state transition, and a safe delivery category; they exclude prospect names, comments, email addresses, bodies, and transport errors.

All pages are private and `no-store`. Comments are escaped and rendered as plain text. Public review IDs appear in URLs; prospect identity and numeric IDs do not. Manager lists are property-restricted and omit comments. Corporate lists span properties, omit comments and email addresses, prioritize oldest pending work, and show readable status text.

Initial email routing is deliberately test-only. `APPLICATION_REVIEW_TEST_MODE=true` and a valid `APPLICATION_REVIEW_TEST_EMAIL` send submission and decision messages only to the configured test recipient. Missing/invalid configuration fails closed and never falls back to Amanda or a property mailbox. `APP_URL` supplies manager and Corporate links. Production routing requires a later explicit authorization and implementation change.

Submission-review messages link to `APP_URL/corporate/application-reviews/{reviewPublicId}`. Decision messages retain the property-scoped manager URL. The centralized category is granted and revoked independently in Corporate Access; Operations, Admin, corporate employee class, and Property Access never substitute for it.

## Route and access matrix

| Route | Access |
|---|---|
| `GET /community-actions/{propertySlug}/application-reviews` | Authentication and current Property Access |
| `GET /community-actions/{propertySlug}/application-reviews/create` | Authentication and current Property Access |
| `POST /community-actions/{propertySlug}/application-reviews` | Authentication, CSRF, and current Property Access |
| `GET /community-actions/{propertySlug}/application-reviews/{reviewPublicId}` | Authentication and current Property Access to the record property |
| `GET /corporate/application-reviews` | Effective Application Reviews access |
| `GET /corporate/application-reviews/{reviewPublicId}` | Effective Application Reviews access |
| `POST /corporate/application-reviews/{reviewPublicId}/decision` | Effective Application Reviews access and CSRF |
