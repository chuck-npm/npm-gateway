# Gateway Constitution

1. Gateway is the system of record for NPM business rules.
2. Each business rule exists exactly once.
3. The database stores facts, application services enforce rules, and clients present results.
4. Meaningful actions are auditable.
5. Secrets are not stored unnecessarily.
6. Presentation layers are replaceable.
7. External integrations are adapters behind application-facing contracts.
8. Gateway preserves institutional knowledge in code and maintained documentation.
9. AI is an optional, non-privileged client using the same services and authorization as every other client.
10. Only application services begin, commit, or roll back transactions (Gateway Rule 013).
11. Services may call services; repositories call neither services nor repositories (Gateway Rule 014).
12. Repositories own persistence, not business policy; services enforce rules and presentation only presents them (Gateway Rule 015).
13. Every business entity has one approved creation service. Commands, controllers, imports, jobs, APIs, and AI clients never write business rows directly (No-Orphans Rule).
14. Future AI clients receive no special access (Gateway Rule 016).
15. Every service is browser-independent and testable without a browser (Gateway Rule 020).

The dependency direction is presentation/client → application service → repository → database. The composition root injects dependencies; business services do not retrieve the container.
