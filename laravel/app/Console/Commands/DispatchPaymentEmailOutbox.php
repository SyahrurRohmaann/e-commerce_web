<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPaymentEmailOutbox;
use App\Models\PaymentOutboxEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DispatchPaymentEmailOutbox extends Command
{
    protected $signature = 'payments:dispatch-email-outbox {--limit=100}';

    protected $description = 'Dispatch durable pending payment email events';

    public function handle(): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $staleBefore = now()->subMinutes(10);

        $claims = collect();

        while ($claims->count() < $limit) {
            $event = PaymentOutboxEvent::query()
                ->whereNull('processed_at')
                ->where(function ($query) use ($staleBefore) {
                    $query->whereNull('locked_at')->orWhere('locked_at', '<', $staleBefore);
                })
                ->orderBy('id')
                ->first();

            if (! $event) {
                break;
            }

            $claimToken = (string) Str::uuid();
            $claimed = PaymentOutboxEvent::query()
                ->whereKey($event->id)
                ->whereNull('processed_at')
                ->where(function ($query) use ($staleBefore) {
                    $query->whereNull('locked_at')->orWhere('locked_at', '<', $staleBefore);
                })
                ->update([
                    'claim_token' => $claimToken,
                    'locked_at' => now(),
                ]);

            if ($claimed === 1) {
                $claims->push([$event->id, $claimToken]);
            }
        }

        $claims->each(fn (array $claim) => ProcessPaymentEmailOutbox::dispatch(...$claim));

        return self::SUCCESS;
    }
}
