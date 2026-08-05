# Employee privacy

Emergency Contact Information is restricted personal information. Gateway stores one current contact per employee in `employee_emergency_contacts`; it is excluded from directories, general employee payloads, notifications, Company Notices, exports, email, logs, and ordinary audit metadata.

HR access is read-only and limited to completed records. Corporate and Manager employees own their self-service values. Current responsible Property Managers may maintain Maintenance ECI only within their current authorized property scope. HR cannot alter ECI values.

Safe audits record only the employee public ID, whether the operation created or updated the row, and whether an alternate phone is present. Contact names, relationship, phone numbers, submitted form data, and prior values are never audited. Updates replace the current values; version history is not retained in the initial implementation.

The self-service response uses `Cache-Control: private, no-store` to reduce browser and shared-cache retention.

HR ECI list, detail, and edit responses use the same no-store policy. List rows expose completion status only. Viewing another employee's restricted record and HR create/update operations produce sanitized target-employee audit events without contact values. Successful reminder history records only non-private delivery-category metadata and supports a 24-hour per-employee cooldown.
