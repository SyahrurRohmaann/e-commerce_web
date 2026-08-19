# Payment email delivery contract

Payment success email uses a durable database outbox with **at-least-once** delivery. Payment state and stock deduction remain transactional and idempotent. A stable event key prevents multiple outbox records for one transaction. The worker rotates each dispatcher claim into a one-execution token and refreshes its lease before SMTP, fencing stale, retried, or duplicate queued jobs from sending under the same claim.

SMTP does not provide a durable idempotency contract. A rare duplicate email can therefore occur if SMTP accepts a message and the worker terminates before `processed_at` commits. This is an accepted residual risk. Recipients must treat payment email as a notification, not the payment system of record.

The worker timeout is five minutes and the stale-claim lease is ten minutes. Database and Redis queue `retry_after` defaults are ten minutes. Queue `retry_after` and SMTP/network timeouts must be configured so a worker cannot continue sending after its claim becomes reclaimable: SMTP/network timeout must be below five minutes, and queue `retry_after` must exceed five minutes without exceeding the stale-claim lease. Operators must alert on repeated attempts and reconcile persistent events without deleting transaction records.
