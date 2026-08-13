# NPM Gateway production checklist

## Database and application

- [ ] Backups and rollback plan confirmed.
- [ ] Pending migrations reviewed and applied with migration credentials.
- [ ] `php bin/gateway schema:verify` passes.
- [ ] Application configuration and private credentials are present for the production identity.

## Background Workers

- [ ] Migration 022 supporting the Marketing Flyer notification outbox is applied.
- [ ] Marketing Flyer notification Task Scheduler task is created.
- [ ] Task executes `php bin/gateway marketing-flyer-notifications:work` every minute.
- [ ] Task runs under the intended production identity with the Gateway root as its working directory.
- [ ] Worker command runs successfully manually.
- [ ] `php bin/gateway marketing-flyer-notifications:check` reports healthy.
- [ ] Heartbeat timestamp updates after scheduled execution.
- [ ] A test outbox notification is delivered only to the approved test recipient.
- [ ] Retry/failure diagnostics have been checked.

See [marketing-flyer-worker.md](marketing-flyer-worker.md) for the exact installation and verification procedure.
