<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_normalized_identity_shares_one_bucket(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $email = $attempt % 2 === 0 ? 'User@Example.COM' : ' user@example.com ';
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->postJson('/api/login', ['email' => $email, 'password' => 'wrong-password'])
                ->assertStatus(422);
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);

        $response->assertTooManyRequests();
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_register_normalized_identity_shares_one_bucket(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $email = $attempt % 2 === 0 ? 'New@Example.COM' : ' new@example.com ';
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
                ->postJson('/api/register', ['email' => $email])
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/api/register', ['email' => 'new@example.com'])
            ->assertTooManyRequests();
    }

    public function test_authenticated_checkout_uses_user_identity(): void
    {
        Route::middleware(['api', 'throttle:checkout'])->post('/api/_task-two/checkout-limit', fn () => response()->noContent());
        $first = User::factory()->create();
        $second = User::factory()->create();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($first, 'sanctum')->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
                ->postJson('/api/_task-two/checkout-limit')->assertNoContent();
        }
        $this->actingAs($first, 'sanctum')->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
            ->postJson('/api/_task-two/checkout-limit')->assertTooManyRequests();
        $this->actingAs($second, 'sanctum')->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
            ->postJson('/api/_task-two/checkout-limit')->assertNoContent();
    }

    public function test_guest_checkout_normalizes_email_and_separates_other_guests(): void
    {
        Route::middleware(['api', 'throttle:checkout'])->post('/api/_task-two/checkout-limit', fn () => response()->noContent());
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $email = $attempt % 2 === 0 ? 'Guest@Example.COM' : ' guest@example.com ';
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
                ->postJson('/api/_task-two/checkout-limit', ['guest_email' => $email])->assertNoContent();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
            ->postJson('/api/_task-two/checkout-limit', ['guest_email' => 'guest@example.com'])->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
            ->postJson('/api/_task-two/checkout-limit', ['guest_email' => 'other@example.com'])->assertNoContent();
    }

    public function test_guest_tracking_routes_share_an_ip_bucket_not_tracking_values(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $path = $attempt % 2 === 0
                ? '/api/transactions/guest/'.($attempt + 1).'?token=value-'.$attempt
                : '/api/transactions/track?token=value-'.$attempt;
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->getJson($path)
                ->assertStatus(404);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->getJson('/api/transactions/track?token=fresh-value')->assertTooManyRequests();
    }

    public function test_catalog_routes_share_an_ip_bucket(): void
    {
        for ($attempt = 0; $attempt < 120; $attempt++) {
            $path = $attempt % 2 === 0 ? '/api/catalog' : '/api/catalog/'.($attempt + 1);
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.6'])->getJson($path);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.6'])
            ->getJson('/api/catalog')->assertTooManyRequests();
    }

    public function test_webhook_authentication_survives_a_retry_friendly_ip_limit(): void
    {
        config(['services.xendit.callback_token' => 'test-only-callback-token']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])
            ->postJson('/api/webhook/xendit', [])->assertUnauthorized();

        for ($attempt = 1; $attempt < 120; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])
                ->withHeader('x-callback-token', 'test-only-callback-token')
                ->postJson('/api/webhook/xendit', [])->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])
            ->withHeader('x-callback-token', 'different-token')
            ->postJson('/api/webhook/xendit', [])->assertTooManyRequests();
    }
}
