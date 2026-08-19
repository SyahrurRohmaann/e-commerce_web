# Deployment runbook

This repository does not deploy automatically. Use a staging environment first and keep production activation a separately approved action.

## Required configuration

Set `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, frontend URL, database, mail, queue, cache, CORS allowlist, trusted proxies, Xendit sandbox/production credentials, callback token, and `VITE_API_BASE_URL`. Secrets belong only in the platform secret store. Never commit them.

## Order

1. Confirm backup and rollback point.
2. Verify readiness and maintenance decision.
3. Apply backward-compatible migrations.
4. Release backend, frontend, and queue workers.
5. Run health checks and security-header checks.
6. Monitor errors, queues, payment events, and database health.

Use sandbox payment credentials in staging. Do not enable real payments or run destructive database operations as part of this repository workflow.

## Staging smoke checklist

Catalog and empty/error states; registration/login/logout; admin authorization; sandbox checkout and constrained invoice redirect; valid/invalid/replayed webhook; guest tracking; queued email; error pages; mobile keyboard/accessibility; HTTPS, HSTS, CSP, frame and content-type headers.

Platform-specific URL, credentials, DNS, payment activation, and smoke evidence remain external prerequisites.

## Health and observability

Expose only a sanitized readiness result. Use structured logs with request IDs and no tokens, provider payloads, personal data, or secrets. Configure alerting, error monitoring, queue-failure monitoring, and tested database backups before go-live.

## Rollback trigger

Rollback on failed health checks, elevated error rate, queue failure, data-integrity concern, or payment reconciliation anomaly. Follow `docs/rollback.md` and reconcile provider events before retrying.

## Go/no-go

No production go decision is valid without successful staging evidence, backup verification, security review, and explicit business approval for payment activation.

