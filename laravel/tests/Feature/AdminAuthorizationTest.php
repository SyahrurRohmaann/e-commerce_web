<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_mutate_admin_resources(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::create(['name' => 'Existing']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Existing product',
            'price' => 10000,
            'stock' => 2,
        ]);

        $requests = [
            ['postJson', '/api/admin/categories', ['name' => 'Blocked']],
            ['putJson', "/api/admin/categories/{$category->id}", ['name' => 'Blocked']],
            ['deleteJson', "/api/admin/categories/{$category->id}", []],
            ['postJson', '/api/admin/products', [
                'category_id' => $category->id, 'name' => 'Blocked', 'price' => 1, 'stock' => 1,
            ]],
            ['putJson', "/api/admin/products/{$product->id}", ['name' => 'Blocked']],
            ['deleteJson', "/api/admin/products/{$product->id}", []],
        ];

        foreach ($requests as [$method, $uri, $payload]) {
            $this->actingAs($customer)->{$method}($uri, $payload)->assertForbidden();
        }
    }

    public function test_customer_cannot_mutate_transactions(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $transaction = Transaction::create([
            'user_id' => $customer->id,
            'total_amount' => 10000,
            'status' => 'PENDING',
            'customer_name' => 'Customer',
            'customer_phone' => '08123456789',
            'shipping_address' => 'Address',
            'shipping_city' => 'Jakarta',
            'shipping_postal_code' => '12345',
            'shipping_cost' => 0,
            'shipping_status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->putJson("/api/admin/transactions/{$transaction->id}/status", ['status' => 'PAID'])
            ->assertForbidden();
    }

    public function test_user_cannot_read_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::create([
            'user_id' => $owner->id,
            'total_amount' => 10000,
            'status' => 'PENDING',
            'customer_name' => 'Owner',
            'customer_phone' => '08123456789',
            'shipping_address' => 'Address',
            'shipping_city' => 'Jakarta',
            'shipping_postal_code' => '12345',
            'shipping_cost' => 0,
            'shipping_status' => 'pending',
        ]);

        $this->actingAs($other)
            ->getJson("/api/transactions/{$transaction->id}")
            ->assertForbidden();
    }
}
