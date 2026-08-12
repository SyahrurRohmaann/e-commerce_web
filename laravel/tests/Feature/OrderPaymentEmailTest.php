<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Mail\OrderPaymentSuccessMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderPaymentEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_when_transaction_becomes_paid_via_webhook()
    {
        Mail::fake();

        $category = Category::create(['name' => 'General']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'price' => 100000,
            'stock' => 10,
        ]);

        $transaction = Transaction::create([
            'tracking_token' => 'test-token-123',
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
            'external_id' => 'INV-' . $transaction->id,
            'status' => 'PAID',
            'payment_channel' => 'QRIS',
        ];

        $response = $this->withHeaders([
            'x-callback-token' => env('XENDIT_CALLBACK_TOKEN'),
        ])->postJson('/api/webhook/xendit', $payload);
        $response->assertStatus(200);

        Mail::assertSent(OrderPaymentSuccessMail::class, function ($mail) {
            return $mail->hasTo('guest@example.com') && $mail->transaction->tracking_token === 'test-token-123';
        });
    }
}
