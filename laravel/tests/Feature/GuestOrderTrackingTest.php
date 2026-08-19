<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_tracking_requires_exact_token_and_does_not_disclose_order(): void
    {
        $transaction = Transaction::create([
            'tracking_token' => 'high-entropy-token',
            'total_amount' => 10000,
            'status' => 'PENDING',
            'customer_name' => 'Guest',
            'customer_phone' => '08123456789',
            'guest_email' => 'guest@example.test',
            'shipping_address' => 'Address',
            'shipping_city' => 'Jakarta',
            'shipping_postal_code' => '12345',
            'shipping_cost' => 0,
            'shipping_status' => 'pending',
        ]);

        $this->getJson("/api/transactions/guest/{$transaction->id}?token=wrong-token")
            ->assertNotFound()
            ->assertJsonMissing(['guest_email' => 'guest@example.test']);
        $this->getJson("/api/transactions/guest/{$transaction->id}")->assertBadRequest();
        $this->getJson("/api/transactions/guest/{$transaction->id}?token=high-entropy-token")
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id);
    }
}
