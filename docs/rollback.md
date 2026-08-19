# Rollback runbook

1. Declare the incident and stop further release changes.
2. Preserve request IDs, sanitized metrics, and payment event IDs; do not copy secrets or raw provider payloads.
3. Roll back the application and frontend to the last verified commit.
4. Roll back workers and reset configuration/cache using the platform's secret/config store.
5. Never reverse a migration destructively. Use a backward-compatible corrective migration or restore a verified backup under the approved database procedure.
6. Verify readiness, authentication, authorization, checkout sandbox behavior, queues, and security headers.
7. Reconcile payment-provider events and database transaction states before retrying webhooks.
8. Document impact, root cause, recovery evidence, and follow-up tests.

Production rollback requires platform access and an approved incident decision. This repository does not perform it automatically.

Next: test rollback procedures in staging before any production release.
