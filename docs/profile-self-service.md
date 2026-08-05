# Profile self-service

Authenticated Corporate and Manager employees can maintain their own Emergency Contact Information at `GET|POST /my/emergency-contact`. The page is linked immediately before Sign Out in the user menu and requires authentication only; Corporate Category Access is unrelated.

Identity always comes from the authenticated session. The route accepts no employee, user, username, or emergency-contact identifier, so Admin or category membership cannot select another employee. The form creates the employee's single row on first save and updates that same row later. It never sends email or publishes a Gateway notification.

Maintenance portal and self-service access remain unavailable; Maintenance ECI is maintained only through the authorized HR workspace.

HR-authorized users have a separate restricted workspace at `/corporate/human-resources/emergency-contacts`. HR owns completeness oversight and follow-up, may view completed ECI, and cannot create or alter ECI values. Corporate and Manager employees maintain their own record at `/my/emergency-contact`. A current responsible Property Manager maintains ECI for active Maintenance employees within that manager’s current authorized property scope at `/manager/maintenance/{employeePublicId}/emergency-contact`.

Missing Corporate reminders go to the employee’s approved business email. Missing Manager and Maintenance reminders go to the current primary property’s stored manager mailbox; Maintenance employees never receive Gateway email. Reminder delivery creates no Gateway notification or acknowledgment rows.
