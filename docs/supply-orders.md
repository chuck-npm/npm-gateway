# Supply Orders

Community Actions Supply Orders lets a manager with current Property Access submit and review immutable supply requests for the selected active community. Each record stores a public ID, authoritative property and submitter identities, Gateway timestamp, canonical sanitized HTML, and server-derived plain text. The lifecycle is exactly one event—Submitted—with no status, history table, edit, delete, approval, completion, return, or processing queue.

The history page is property-scoped and newest-first, with a compact plain-text preview and read-only detail. Supplies Requested uses the established Quill formatting subset: bold, italic, underline, ordered/bullet lists, indentation, safe HTTP(S) links, and clear formatting. Scripts, styles, event attributes, media, embeds, unsafe URL schemes, arbitrary classes/styles, and attachments are forbidden. The textarea remains a progressive fallback and server sanitization is authoritative.

After the database transaction commits, Gateway emails the configured recipient using the standard workflow renderer. Configure `SUPPLY_ORDERS_RECIPIENT_EMAIL=noc@npmparks.com`. For safe acceptance, set `SUPPLY_ORDERS_TEST_MODE=true` and a valid `SUPPLY_ORDERS_TEST_EMAIL`; test mode fails closed rather than falling back to production. A delivery failure never removes the persisted order. Purchase, delivery, and Trillian follow-up happen outside Gateway.

Migration `202608090020_supply_orders` creates the shared table. The future Universal Tools Corporate entry point must reuse this repository, sanitizer, service, email renderer, and table while applying its own authorization/context boundary; it must not create a parallel order store.
