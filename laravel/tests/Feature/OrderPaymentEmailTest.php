<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaymentEmailOutbox;
use App\Mail\OrderPaymentSuccessMail;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderPaymentEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_when_transaction_becomes_paid_via_webhook()
    {
        Mail::fake();
        Queue::fake();
        config(['services.xendit.callback_token' => 'test-callback-token']);

        $category = Category::create(['name' => 'General']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'price' => 100000,
            'stock' => 10,
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

        $payload = [
            'id' => 'xendit-invoice-1',
            'external_id' => 'INV-'.$transaction->id,
            'status' => 'PAID',
            'amount' => 125000,
            'payment_channel' => 'QRIS',
        ];

        $response = $this->withHeaders([
            'x-callback-token' => 'test-callback-token',
        ])->postJson('/api/webhook/xendit', $payload);
        $response->assertStatus(200);

        Artisan::call('payments:dispatch-email-outbox');
        $job = null;
        Queue::assertPushed(ProcessPaymentEmailOutbox::class, function (ProcessPaymentEmailOutbox $queued) use (&$job) {
            $job = $queued;

            return true;
        });
        $job->handle();

        Mail::assertSent(OrderPaymentSuccessMail::class, function ($mail) {
            return $mail->hasTo('guest@example.com') && $mail->transaction->tracking_token === 'test-token-123';
        });
    }
}
