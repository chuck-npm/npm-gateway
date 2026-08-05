# HR employee onboarding

Employee creation, assignment, Gateway user creation, and their transactional audits commit together. Gateway then sends the existing SECURE credential notification to configured HR recipients. A secure-email failure leaves the created records intact and prevents company publication.

After secure delivery succeeds, Gateway publishes one `employee_created` notification with only name, job title, start date, company phone, business email, and primary property. The subject employee's class does not control whether the announcement is published. Publication atomically materializes only active Corporate and Manager recipients through the platform resolver, so a Maintenance hire may be announced but neither that hire nor existing Maintenance employees receive it. The non-sensitive announcement is then sent once per distinct normalized business email. Delivery failures do not remove the notice and are reported without addresses or SMTP details.
