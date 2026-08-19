# Security operations

## Secure defaults

Production must use `APP_DEBUG=false`, HTTPS URLs, a narrow CORS allowlist, configured trusted proxies, secure cookies, HSTS only after HTTPS is verified, a compatible CSP, `X-Content-Type-Options: nosniff`, a restrictive referrer policy, and frame protection.

## Secrets and privacy

Keep API keys, callback tokens, database credentials, mail credentials, and signing material in the deployment secret manager. Rotate on suspected exposure. Logs and client errors must exclude tokens, provider exception bodies, SQL, stack traces, signed URLs, and personal data.

## Monitoring

Alert on authentication abuse, authorization failures, repeated checkout failures, webhook replay/mismatch, queue failures, database errors, latency, and unusual 429 rates. Retain only the minimum sanitized operational data required by policy.

## Release and incident controls

Require peer review, CI, dependency/security audits, backup verification, staging smoke evidence, and rollback readiness. Never activate real payments or deploy production automatically from this repository. Investigate and reconcile payment events before replaying them.

## Residual external checks

Final domains, platform headers/CORS/trusted-proxy behavior, monitoring provider, backup restoration, and staging credentials/URL require platform-specific verification and are not claimed by repository-local tests.

Next: validate this checklist against the chosen staging platform before go-live.