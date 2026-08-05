# Credit Card Purchases

Credit Card Purchases records company-card transactions after they occur; it is not an approval workflow. Each record is scoped to an active property resolved from the route and effective Property Access. Submitted By always comes from the authenticated Gateway session. Purchased By is independently selected by employee public ID from active, current property assignments and does not grant Gateway access.

The reconciliation record preserves card last four, purchase date, exact decimal amount, vendor, description, purchaser, submitter, timestamps, and receipt state. A PDF, JPEG, or PNG receipt is stored privately through the existing Wasabi-compatible abstraction; automated certification uses fake storage and makes no provider request. When no receipt is available, the submitter must explicitly declare it missing and explain why. A later receipt upload preserves that explanation and appends history.

Purchases and history are never deleted. Version 1 supports posting, viewing, missing-receipt documentation, and adding a missing receipt. Controlled corrections and voiding are deferred until authorization policy is approved; no unsafe direct edit is provided. Corporate Finance contains a planned reconciliation card only. The existing Universal Tools Credit Card Purchases card remains in place and is intended to move into Corporate later.

## Guarded real-storage browser acceptance

Automated tests must continue to inject fake object storage. To perform a deliberate browser acceptance test against Wasabi, configure `APP_ENV=local`, `CREDIT_CARD_RECEIPT_REAL_STORAGE_TESTING=true`, `WASABI_CREDIT_CARD_RECEIPTS_PREFIX=credit-card-receipts/`, and `WASABI_CREDIT_CARD_RECEIPTS_TEST_PREFIX=credit-card-receipts/test/`. The explicit flag is required; local mode alone never selects test storage. Missing, malformed, equal, or non-nested receipt prefixes block the upload.

Receipt keys contain only the configured prefix, UTC year/month partitions, a cryptographically random identifier, and an extension derived from the detected MIME type. The original filename and purchase, property, employee, card, and amount data are never used in the key.

Attached receipts use the shared secure Gateway document viewer described in `docs/document-viewer.md`. The detail page supplies only safe presentation metadata and independently authorized view/download routes; private provider locations remain server-side.

For acceptance, verify that the upload succeeds through Gateway, its randomized key and detected MIME/size/checksum are recorded, the object exists only under `credit-card-receipts/test/`, direct public access fails, authorized Gateway viewing succeeds, cross-property access returns 403, neither a raw provider URL nor object key appears in markup, and receipt history contains the upload. Confirm the normal prefix has no acceptance object outside its `test/` subtree.

After reviewing the records, `php bin/gateway credit-card-receipts:test-cleanup --confirm` previews provider keys before deletion. It runs only in local/development with the explicit testing flag and isolated test prefix. It refuses cleanup if any database metadata exists under that prefix, never changes database records, and is never run automatically. Remove test records through an intentionally designed fixture process before using it to delete orphaned provider objects.
