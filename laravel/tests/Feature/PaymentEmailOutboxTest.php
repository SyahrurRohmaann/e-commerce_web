<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaymentEmailOutbox;
use App\Models\PaymentOutboxEvent;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\PaymentTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class PaymentEmailOutboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_transition_durably_creates_one_pending_outbox_event(): void
    {
        $transaction = $this->transaction();

        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID', 'QRIS');
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID', 'QRIS');

        $event = PaymentOutboxEvent::sole();
        $this->assertSame("payment-success:{$transaction->id}", $event->idempotency_key);
        $this->assertNull($event->processed_at);
        $this->assertSame(0, $event->attempts);
    }

    public function test_send_failure_leaves_outbox_event_retryable(): void
    {
        $transaction = $this->transaction();
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');
        $event = PaymentOutboxEvent::sole();
        $event->update([
            'claim_token' => 'failure-owner',
            'locked_at' => now(),
        ]);
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('transport failed'));

        try {
            (new ProcessPaymentEmailOutbox($event->id, 'failure-owner'))->handle();
            $this->fail('Expected mail transport failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('transport failed', $exception->getMessage());
        }

        $event->refresh();
        $this->assertNull($event->processed_at);
        $this->assertSame(1, $event->attempts);
        $this->assertNotNull($event->last_attempt_at);
        $this->assertNull($transaction->fresh()->payment_email_sent_at);
    }

    public function test_successful_processing_marks_event_complete(): void
    {
        Mail::fake();
        Queue::fake();
        $transaction = $this->transaction();
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');

        Artisan::call('payments:dispatch-email-outbox');
        $job = null;
        Queue::assertPushed(ProcessPaymentEmailOutbox::class, function (ProcessPaymentEmailOutbox $queued) use (&$job) {
            $job = $queued;

            return true;
        });
        $job->handle();

        $event = PaymentOutboxEvent::sole();
        $this->assertNotNull($event->processed_at);
        $this->assertSame(1, $event->attempts);
        $this->assertNotNull($transaction->fresh()->payment_email_sent_at);
    }

    public function test_duplicate_queued_job_cannot_send_without_current_claim_ownership(): void
    {
        Mail::fake();
        Queue::fake();
        $transaction = $this->transaction();
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');

        Artisan::call('payments:dispatch-email-outbox');
        $owner = null;
        Queue::assertPushed(ProcessPaymentEmailOutbox::class, function (ProcessPaymentEmailOutbox $queued) use (&$owner) {
            $owner = $queued;

            return true;
        });

        (new ProcessPaymentEmailOutbox($owner->eventId, 'not-the-current-owner'))->handle();
        Mail::assertNothingSent();

        $owner->handle();
        Mail::assertSentCount(1);
        $this->assertNotNull(PaymentOutboxEvent::sole()->processed_at);
    }

    public function test_duplicate_job_with_same_dispatcher_claim_can_only_send_once(): void
    {
        Mail::fake();
        $transaction = $this->transaction();
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');
        $event = PaymentOutboxEvent::sole();
        $event->update(['claim_token' => 'shared-dispatch-claim', 'locked_at' => now()]);

        $first = new ProcessPaymentEmailOutbox($event->id, 'shared-dispatch-claim');
        $duplicate = new ProcessPaymentEmailOutbox($event->id, 'shared-dispatch-claim');

        $first->handle();
        $duplicate->handle();

        Mail::assertSentCount(1);
        $this->assertNotNull($event->fresh()->processed_at);
    }

    public function test_stale_claim_is_reclaimed_with_new_owner_and_old_job_cannot_send(): void
    {
        Mail::fake();
        Queue::fake();
        $transaction = $this->transaction();
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');
        $event = PaymentOutboxEvent::sole();
        $event->update([
            'claim_token' => 'stale-owner',
            'locked_at' => now()->subMinutes(11),
        ]);

        Artisan::call('payments:dispatch-email-outbox');
        $replacement = null;
        Queue::assertPushed(ProcessPaymentEmailOutbox::class, function (ProcessPaymentEmailOutbox $queued) use (&$replacement) {
            $replacement = $queued;

            return true;
        });

        $this->assertNotSame('stale-owner', $event->fresh()->claim_token);
        (new ProcessPaymentEmailOutbox($event->id, 'stale-owner'))->handle();
        Mail::assertNothingSent();

        $replacement->handle();
        Mail::assertSentCount(1);
    }

    public function test_owner_that_loses_claim_during_send_cannot_complete_replacement_claim(): void
    {
        $transaction = $this->transaction();
        app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');
        $event = PaymentOutboxEvent::sole();
        $event->update(['claim_token' => 'original-owner', 'locked_at' => now()]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andReturnUsing(function () use ($event) {
            $event->update(['claim_token' => 'replacement-owner', 'locked_at' => now()]);
        });

        (new ProcessPaymentEmailOutbox($event->id, 'original-owner'))->handle();

        $event->refresh();
        $this->assertSame('replacement-owner', $event->claim_token);
        $this->assertNull($event->processed_at);
        $this->assertNull($transaction->fresh()->payment_email_sent_at);
    }

    public function test_product_quantities_are_aggregated_and_failure_rolls_back_all_stock(): void
    {
        $category = Category::create(['name' => 'Stock']);
        $first = Product::create([
            'category_id' => $category->id,
            'name' => 'First',
            'slug' => 'first',
            'description' => 'First',
            'price' => 10000,
            'stock' => 5,
        ]);
        $second = Product::create([
            'category_id' => $category->id,
            'name' => 'Second',
            'slug' => 'second',
            'description' => 'Second',
            'price' => 10000,
            'stock' => 5,
        ]);
        $transaction = $this->transaction();

        // Insert reverse product order. The service locks and processes ascending IDs.
        TransactionItem::create(['transaction_id' => $transaction->id, 'product_id' => $second->id, 'product_name' => 'Second', 'quantity' => 1, 'price' => 10000]);
        TransactionItem::create(['transaction_id' => $transaction->id, 'product_id' => $first->id, 'product_name' => 'First', 'quantity' => 3, 'price' => 10000]);
        TransactionItem::create(['transaction_id' => $transaction->id, 'product_id' => $first->id, 'product_name' => 'First', 'quantity' => 3, 'price' => 10000]);

        try {
            app(PaymentTransitionService::class)->transition($transaction->id, 'PAID');
            $this->fail('Expected aggregated insufficient stock failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Insufficient stock while processing payment.', $exception->getMessage());
        }

        $this->assertSame(5, $first->fresh()->stock);
        $this->assertSame(5, $second->fresh()->stock);
        $this->assertSame('PENDING', $transaction->fresh()->status);
        $this->assertSame(0, PaymentOutboxEvent::count());
    }

    public function test_job_timeout_stays_below_stale_claim_lease(): void
    {
        $job = new ProcessPaymentEmailOutbox(1, 'owner');

        $this->assertLessThan(600, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
        $this->assertGreaterThan($job->timeout, config('queue.connections.database.retry_after'));
        $this->assertGreaterThan($job->timeout, config('queue.connections.redis.retry_after'));
    }

    private function transaction(): Transaction
    {
        return Transaction::create([
            'tracking_token' => 'outbox-test-token',
            'xendit_invoice_id' => 'outbox-invoice',
            'total_amount' => '125000.00',
            'status' => 'PENDING',
            'customer_name' => 'Outbox Test',
            'customer_phone' => '08123456789',
            'guest_email' => 'guest@example.com',
            'shipping_address' => 'Test Address',
            'shipping_city' => 'Jakarta',
            'shipping_postal_code' => '12345',
            'shipping_cost' => 25000,
            'shipping_status' => 'pending',
        ]);
    }
}
