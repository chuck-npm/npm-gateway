# Marketing Flyers

## Production dependency

Marketing Flyer uploads work without synchronous email, but production email delivery requires the scheduled notification worker. Apply Migration 022 and install the one-minute Windows Task Scheduler job described in [deployment/marketing-flyer-worker.md](deployment/marketing-flyer-worker.md). Verify it with `php bin/gateway marketing-flyer-notifications:check` before production acceptance.

Notification delivery metadata is infrastructure state, not a Flyer lifecycle status. Upload and replacement persist one recipient-specific durable delivery per normalized production recipient, or exactly one configured test delivery in test mode. Delete cancels undelivered rows. Each event uses an immutable private attachment snapshot so rapid replacements deliver their intended revisions.

Corporate Marketing users with effective `marketing` Category Access can manage community flyers. Property Access is intentionally irrelevant. Only active, non-Corporate communities are eligible, and the permanent `properties.property_code` is the authoritative two-letter marketing designation.

Each property/month has one `standard` flyer and optionally one `promo` flyer, enforced by a unique database key. Months begin August 2026 and extend automatically through at least the Gateway-local current month plus 12 months. Standard names are `<CODE>-YYYY-MM.<ext>`; Promo names are `<CODE>-Promo.<ext>`. The sole durable asset uses `flyers/YYYY/MM/`; no thumbnail derivative is created.

PNG and JPEG portrait images up to 20 MB are accepted after server MIME inspection and decode validation. The original remains unchanged. Assets remain private and are streamed only through authorized Gateway routes.

Re-uploading the same property/month/type replaces the logical row. Assets are staged before the metadata transaction; failed persistence triggers storage cleanup, while email failure leaves the saved flyer intact and returns a warning. Permanent deletion removes both objects before removing metadata and reports any remaining state.

Current active property-manager and assistant-manager assignments supply deduplicated employee business-email recipients. One CTA-only email is sent per recipient without CC or attachments. In production the Download Flyer CTA targets the assignment-protected Community Actions download route. `MARKETING_FLYERS_TEST_MODE=true` routes exclusively to the valid `MARKETING_FLYERS_TEST_EMAIL` and uses the Corporate Marketing download route; missing/invalid test email fails closed. PDFs and public Wasabi access are unsupported.
