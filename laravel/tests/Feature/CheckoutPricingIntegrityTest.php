<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPricingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_supplied_price_and_total_are_not_trusted(): void
    {
        $category = Category::create(['name' => 'General']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Secure product',
            'price' => 100000,
            'stock' => 3,
        ]);

        $response = $this->postJson('/api/checkout', [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 1,
            ]],
            'total_amount' => 1,
            'customer_name' => 'Guest',
            'customer_phone' => '08123456789',
            'guest_email' => 'guest@example.test',
            'shipping_address' => 'Address',
            'shipping_city' => 'Jakarta',
            'shipping_postal_code' => '12345',
        ]);

        // Payment provider configuration is intentionally absent in tests; the request
        // must get past validation and fail only when the external invoice is created.
        $this->assertNotSame(422, $response->status());
        $this->assertDatabaseHas('transactions', ['total_amount' => 125000]);
        $this->assertDatabaseMissing('transactions', ['total_amount' => 1]);
    }

    public function test_invalid_quantities_are_rejected(): void
    {
        $category = Category::create(['name' => 'General']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Product', 'price' => 10, 'stock' => 1]);

        $this->postJson('/api/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 0]],
            'customer_name' => 'Guest', 'customer_phone' => '08123456789',
            'guest_email' => 'guest@example.test', 'shipping_address' => 'Address',
            'shipping_city' => 'Jakarta', 'shipping_postal_code' => '12345',
        ])->assertUnprocessable();
    }
}
