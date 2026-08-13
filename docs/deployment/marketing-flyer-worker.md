# Marketing Flyer notification worker

Marketing Flyer uploads persist the flyer and a durable notification delivery intent. Email is delivered separately by the CLI worker. Production must run the worker every minute.

## Prerequisites

- Apply Migration 022 (`202608120022_marketing_flyer_notification_outbox`) with the normal migration procedure.
- Use the production PHP executable and Gateway `.env` containing the normal database, private Wasabi, application URL, and SMTP configuration.
- The scheduled identity needs read/execute access to PHP and the project plus application database CRUD access. Flyer notification delivery does not read Wasabi or create temporary attachment files.

## Windows Task Scheduler

Example installation values (replace paths with the deployed paths):

- Program/script: `C:\path\to\php.exe`
- Add arguments: `C:\path\to\npm-gateway\bin\gateway marketing-flyer-notifications:work`
- Start in: `C:\path\to\npm-gateway`
- Trigger: Daily
- Repeat task every: 1 minute
- For a duration of: Indefinitely
- Run whether the user is logged on or not
- Use the intended least-privileged production service identity
- Configure “If the task is already running” as “Do not start a new instance.” The worker also uses atomic database claims as a second concurrency boundary.

Task Scheduler captures the process exit code. Redirect standard output/error through the deployment's approved task logging wrapper or configure Task Scheduler operational history; never place credentials in arguments or logs.

## Verification

From the Gateway project root, run:

```powershell
C:\path\to\php.exe .\bin\gateway marketing-flyer-notifications:work
C:\path\to\php.exe .\bin\gateway marketing-flyer-notifications:check
```

Exit code `0` means the worker infrastructure ran successfully, including an empty batch. Recipient delivery failures safely recorded as retry/failed rows do not make the invocation fail. A nonzero worker exit indicates an unexpected infrastructure failure. The health command exits nonzero when the heartbeat is missing or older than five minutes.

To test a queued notification, upload through the authorized Marketing Flyers workflow, confirm the browser completes before email delivery, run the worker manually, then run the health check. Do not inspect recipient addresses in task logs.

## Operations

- Pending and retry counts, failed count, oldest pending age, and heartbeat are available through `marketing-flyer-notifications:check`.
- Retry schedule: immediate attempt, then approximately 1, 5, 15, 30, and 60 minutes; the sixth failed attempt is terminal.
- Claims older than 15 minutes can be reclaimed after an interrupted process.
- Delivered and failed rows remain as operational metadata. Legacy attachment columns remain empty for compatibility with Migration 022 and are not operationally used.
- To disable safely, stop/disable the scheduled task and allow an active invocation to finish. Uploads continue queuing durable intents. Re-enable the task and run it manually once before restoring the schedule.
- If heartbeat is stale, verify task identity, “Start in” path, PHP path, `.env`, database connectivity, and task history. If rows retry, verify SMTP and Wasabi access without printing secrets or recipients.

Rollback of Migration 022 is permitted only when the outbox is empty. Disabling the worker does not roll back Flyer uploads. Never drop delivery metadata while queued work exists.
