# Call Logs

Gateway Call Logs imports Lumen IVR `Detailed Report` Excel `.xls` workbooks as an operational-data foundation. The required columns are `Calling TN`, `Called TN`, `Start Time`, `Release Time`, and `Call Duration`.

Called TN values resolve through `call_log_destinations`, never directly through `properties.ivr_number`. Pine Hill/Pine Manor's shared number reports to Pine Hill; Highridge/Sizemore's shared number reports to Highridge. Suburban is an external destination named `Suburban` and is not a Gateway property.

Telephone numbers are normalized to E.164. Source timestamps are stored as `DATETIME(3)`, and Lumen duration remains authoritative seconds with millisecond precision in `DECIMAL(12,3) UNSIGNED`. Attribution is frozen on each imported call.

Imports validate the complete workbook and reject unknown Called TNs before mutation. One transaction creates the import provenance row, call rows, and a single `admin.call_logs_imported` audit event. Exact-file SHA-256 uniqueness prevents uploading the same binary workbook twice; v1 intentionally performs no guessed row-level deduplication.

The restricted page uses server-side pagination with 100, 250, or 500 rows per page (500 default) ordered by `started_at DESC, id DESC`. Access is limited server-side to the configured protected principal. V1 provides no call editing, deletion, import-history UI, analytics, or reports; the normalized tables are their future foundation.
