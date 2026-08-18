<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('auth.login', fn (Request $request) => Limit::perMinute(5)
            ->by($this->identityKey($request, 'email')));
        RateLimiter::for('auth.register', fn (Request $request) => Limit::perMinute(3)
            ->by($this->identityKey($request, 'email')));
        RateLimiter::for('checkout', function (Request $request) {
            $user = Auth::guard('sanctum')->user();
            $identity = $user
                ? 'user:'.$user->getAuthIdentifier()
                : 'guest:'.$this->normalizedIdentityHash($request->input('guest_email'));

            return Limit::perMinute(10)->by(hash('sha256', $request->ip().'|'.$identity));
        });
        RateLimiter::for('guest-tracking', fn (Request $request) => Limit::perMinute(30)
            ->by(hash('sha256', $request->ip())));
        RateLimiter::for('catalog', fn (Request $request) => Limit::perMinute(120)
            ->by(hash('sha256', $request->ip())));
        RateLimiter::for('webhook.xendit', fn (Request $request) => Limit::perMinute(120)
            ->by(hash('sha256', $request->ip())));
    }

    private function identityKey(Request $request, string $field): string
    {
        return hash('sha256', $request->ip().'|'.$this->normalizedIdentityHash($request->input($field)));
    }

    private function normalizedIdentityHash(mixed $value): string
    {
        $identity = is_string($value) || is_numeric($value) ? trim((string) $value) : '';
        $identity = function_exists('mb_strtolower')
            ? mb_strtolower($identity, 'UTF-8')
            : strtolower($identity);

        return hash('sha256', $identity);
    }
}
