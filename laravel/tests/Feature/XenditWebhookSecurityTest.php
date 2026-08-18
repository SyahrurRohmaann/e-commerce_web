<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentOutboxEvent;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\PaymentTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class XenditWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.xendit.callback_token' => 'expected-callback-token']);
    }

    public function test_missing_server_callback_token_fails_closed(): void
    {
        config(['services.xendit.callback_token' => null]);

        $this->withHeader('x-callback-token', 'attacker-token')
            ->postJson('/api/webhook/xendit', [])
            ->assertStatus(503);
    }

    public function test_missing_or_invalid_callback_token_is_rejected_without_logging_secrets_or_payload(): void
    {
        Log::spy();
        $payload = ['payer_email' => 'private@example.com', 'secret' => 'private-payload-value'];

        $this->postJson('/api/webhook/xendit', $payload)->assertUnauthorized();
        $this->withHeader('x-callback-token', 'received-secret-token')
            ->postJson('/api/webhook/xendit', $payload)
            ->assertUnauthorized();

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_paid_callback_requires_matching_invoice_external_id_and_amount(): void
    {
        [$transaction, $product] = $this->order();

        foreach ([
            ['id' => 'wrong-invoice', 'external_id' => "INV-{$transaction->id}", 'amount' => '125000.00'],
            ['id' => 'xendit-invoice-1', 'external_id' => "INV-{$transaction->id}-other", 'amount' => '125000.00'],
            ['id' => 'xendit-invoice-1', 'external_id' => "INV-{$transaction->id}", 'amount' => '125001.00'],
        ] as $identity) {
            $this->postCallback($identity + ['status' => 'PAID'])->assertStatus(422);
        }

        $this->assertSame('PENDING', $transaction->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_amount_that_only_matches_after_float_rounding_is_rejected(): void
    {
        [$transaction, $product] = $this->order();

        $this->postCallback($this->validPayload($transaction, ['amount' => '125000.004']))
            ->assertStatus(422);

        $this->assertSame('PENDING', $transaction->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_unknown_status_is_rejected(): void
    {
        [$transaction] = $this->order();

        $this->postCallback($this->validPayload($transaction, ['status' => 'UNKNOWN_ENUM_VALUE']))
            ->assertStatus(422);

        $this->assertSame('PENDING', $transaction->fresh()->status);
    }

    public function test_admin_cannot_revert_paid_transaction_to_pending(): void
    {
        [$transaction] = $this->order();
        $transaction->update([
            'status' => 'PAID',
            'payment_processed_at' => now(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->putJson("/api/admin/transactions/{$transaction->id}/status", ['status' => 'PENDING'])
            ->assertStatus(409);

        $this->assertSame('PAID', $transaction->fresh()->status);
    }

    public function test_admin_cannot_make_processed_transaction_expired(): void
    {
        [$transaction] = $this->order();
        $transaction->update(['payment_processed_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->putJson("/api/admin/transactions/{$transaction->id}/status", ['status' => 'EXPIRED'])
            ->assertStatus(409);

        $this->assertSame('PENDING', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->payment_processed_at);
    }

    public function test_settled_callback_is_an_explicit_paid_transition(): void
    {
        Mail::fake();
        [$transaction, $product] = $this->order();

        $this->postCallback($this->validPayload($transaction, ['status' => 'SETTLED']))->assertOk();

        $this->assertSame('PAID', $transaction->fresh()->status);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertDatabaseCount('payment_outbox_events', 1);
    }

    public function test_duplicate_paid_callbacks_decrement_stock_and_create_one_outbox_event(): void
    {
        Mail::fake();
        [$transaction, $product] = $this->order();
        $payload = $this->validPayload($transaction);

        $this->postCallback($payload)->assertOk();
        $this->postCallback($payload)->assertOk();

        $this->assertSame('PAID', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->payment_processed_at);
        $this->assertNull($transaction->fresh()->payment_email_sent_at);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame("payment-success:{$transaction->id}", PaymentOutboxEvent::sole()->idempotency_key);
    }

    public function test_paid_callback_cannot_make_stock_negative(): void
    {
        Mail::fake();
        [$transaction, $product] = $this->order(stock: 0);

        $this->postCallback($this->validPayload($transaction))->assertStatus(409);

        $this->assertSame('PENDING', $transaction->fresh()->status);
        $this->assertNull($transaction->fresh()->payment_processed_at);
        $this->assertSame(0, $product->fresh()->stock);
        Mail::assertNothingSent();
    }

    public function test_missing_transaction_response_is_propagated(): void
    {
        $this->postCallback([
            'id' => 'xendit-invoice-missing',
            'external_id' => 'INV-999999',
            'status' => 'PAID',
            'amount' => '125000.00',
        ])->assertNotFound();
    }

    public function test_transition_deletion_race_is_returned_as_not_found(): void
    {
        [$transaction] = $this->order();
        $payments = $this->mock(PaymentTransitionService::class);
        $payments->shouldReceive('transition')
            ->once()
            ->with($transaction->id, 'PAID', 'QRIS')
            ->andReturnNull();

        $this->postCallback($this->validPayload($transaction))->assertNotFound();
    }

    private function postCallback(array $payload)
    {
        return $this->withHeader('x-callback-token', 'expected-callback-token')
            ->postJson('/api/webhook/xendit', $payload);
    }

    private function validPayload(Transaction $transaction, array $overrides = []): array
    {
        return array_merge([
            'id' => $transaction->xendit_invoice_id,
            'external_id' => "INV-{$transaction->id}",
            'status' => 'PAID',
            'amount' => '125000.00',
            'payment_channel' => 'QRIS',
        ], $overrides);
    }

    private function order(int $stock = 10): array
    {
        $category = Category::create(['name' => 'General']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'price' => 100000,
            'stock' => $stock,
        ]);
        $transaction = Transaction::create([
            'tracking_token' => 'test-token-123',
            'xendit_invoice_id' => 'xendit-invoice-1',
            'total_amount' => 125000,
            'status' => 'PENDING',
            'customer_name' => 'John Guest',
            'customer_phone' => '08123456789',
            'guest_email' => 'guest@example.com',
            'shipping_address' => 'Test Address',
            'shipping_city' => 'Jakarta',
            'shipping_postal_code' => '12345',
            'shipping_cost' => 25000,
            'shipping_status' => 'pending',
        ]);
        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => 100000,
        ]);

        return [$transaction, $product];
    }
}
