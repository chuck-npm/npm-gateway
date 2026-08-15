# Apartments.com

The restricted `Administration → Apartments.com` workspace imports Apartments.com `.xlsx` workbooks as source data for a future weekly owner report. It does not use the Lumen Call Logs schema and does not generate the combined report.

Each workbook must contain exactly one `Phone Calls - count` worksheet with `Property Name`, `Date/Time`, `Caller Phone Number`, `Ring-To Number`, `Tracking Number`, `Call Duration`, `Call Recording`, and `Status`, plus exactly one `Emails - count` worksheet with `Property Name`, `Date/Time`, `Renter Name`, `Renter Phone Number`, and `Renter Email`. The suffix count must equal populated business rows.

Source names map explicitly to authoritative properties: Boulder Trails, Crumley Farms, Flamingo Flats, Highridge, `Maplewind MHC → Maplewind`, `Pearce Point → Pearce Pointe`, Pine Hill, Sizemore, and Wunderpark. Sizemore remains separate from Highridge for Apartments.com. Pine Manor has no Apartments.com ad and has no source mapping; a future report will render `No ad`. Suburban is excluded.

Calls retain source phone text, optional normalized E.164 forms, source status, and the safe recording display value only. Recording hyperlinks are not persisted. Clock-like durations are converted without wrapping to unsigned seconds (`00:08 → 8`, `01:12 → 72`, and `60:00 → 3600`). Email leads retain renter name, source email, and raw phone. Missing, incomplete, or malformed renter phones—including `238`—remain valid leads and produce a null normalized phone.

`apartments_imports` stores workbook provenance and a unique SHA-256 hash. `apartments_property_mappings` is the source-name translation layer. `apartments_calls` and `apartments_email_leads` store normalized source facts with property/date indexes for future weekly counts. All names are resolved before mutation; an unknown name rejects the workbook. The import, calls, leads, and one privacy-safe audit event are written in one transaction, with no speculative row-level deduplication.
