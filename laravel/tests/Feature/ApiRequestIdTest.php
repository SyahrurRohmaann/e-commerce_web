<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ApiRequestIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_response_uses_a_server_generated_request_id(): void
    {
        $response = $this->withHeaders([
            'X-Request-ID' => 'client-value',
            'X-Correlation-ID' => 'client-correlation',
        ])->getJson('/api/catalog');

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertOk();
        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertNotSame('client-value', $requestId);
        $this->assertNotSame('client-correlation', $requestId);
    }

    public function test_unexpected_api_exception_is_sanitized_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        Route::middleware('api')->get('/api/_task-two/throw', function (): never {
            throw new RuntimeException('provider-secret SQLSTATE callback-token stack canary');
        });

        $response = $this->getJson('/api/_task-two/throw');
        $requestId = $response->headers->get('X-Request-ID');

        $response->assertStatus(500)
            ->assertExactJson([
                'message' => 'Internal server error.',
                'request_id' => $requestId,
            ]);
        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertStringNotContainsString('provider-secret', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('callback-token', $response->getContent());
        $this->assertStringNotContainsString('trace', strtolower($response->getContent()));
    }

    public function test_server_http_exception_is_also_sanitized(): void
    {
        config(['app.debug' => true]);
        Route::middleware('api')->get('/api/_task-two/http-throw', function (): never {
            throw new HttpException(500, 'provider-secret-http-canary');
        });

        $response = $this->getJson('/api/_task-two/http-throw');
        $requestId = $response->headers->get('X-Request-ID');

        $response->assertStatus(500)->assertExactJson([
            'message' => 'Internal server error.',
            'request_id' => $requestId,
        ]);
        $this->assertStringNotContainsString('provider-secret-http-canary', $response->getContent());
    }

    public function test_expected_framework_exception_semantics_are_preserved(): void
    {
        $validation = $this->postJson('/api/login', []);
        $validation->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertTrue(Str::isUuid((string) $validation->headers->get('X-Request-ID')));

        $unauthenticated = $this->getJson('/api/profile');
        $unauthenticated->assertUnauthorized();
        $this->assertTrue(Str::isUuid((string) $unauthenticated->headers->get('X-Request-ID')));

        $missing = $this->getJson('/api/route-that-does-not-exist');
        $missing->assertNotFound();
        $this->assertTrue(Str::isUuid((string) $missing->headers->get('X-Request-ID')));
    }
}
