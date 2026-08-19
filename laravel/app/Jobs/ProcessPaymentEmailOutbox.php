<?php

namespace App\Jobs;

use App\Mail\OrderPaymentSuccessMail;
use App\Models\PaymentOutboxEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ProcessPaymentEmailOutbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public array $backoff = [60, 300, 900, 3600];

    // Must stay below the dispatcher's ten-minute stale-claim lease.
    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(public int $eventId, public string $claimToken) {}

    public function handle(): void
    {
        $executionToken = (string) Str::uuid();
        $event = DB::transaction(function () use ($executionToken) {
            $event = PaymentOutboxEvent::with('transaction')
                ->where('claim_token', $this->claimToken)
                ->lockForUpdate()
                ->find($this->eventId);

            if (! $event || $event->processed_at) {
                return null;
            }

            $event->update([
                'attempts' => $event->attempts + 1,
                'last_attempt_at' => now(),
                // Rotate the dispatcher claim into a one-execution fence. A
                // duplicate delivery carrying the old token cannot also send.
                'claim_token' => $executionToken,
                'locked_at' => now(),
            ]);

            return $event->fresh('transaction');
        });

        if (! $event) {
            return;
        }

        try {
            Mail::to($event->recipient)->send(new OrderPaymentSuccessMail($event->transaction));
        } catch (Throwable $exception) {
            PaymentOutboxEvent::query()
                ->whereKey($event->id)
                ->where('claim_token', $executionToken)
                ->whereNull('processed_at')
                ->update([
                    'claim_token' => null,
                    'locked_at' => null,
                ]);
            throw $exception;
        }

        DB::transaction(function () use ($event, $executionToken) {
            $current = PaymentOutboxEvent::query()
                ->where('claim_token', $executionToken)
                ->lockForUpdate()
                ->find($event->id);
            if (! $current || $current->processed_at) {
                return;
            }

            $completedAt = now();
            $current->update([
                'processed_at' => $completedAt,
                'claim_token' => null,
                'locked_at' => null,
            ]);
            $current->transaction()->update(['payment_email_sent_at' => $completedAt]);
        });
    }
}
