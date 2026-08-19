<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\IsAdmin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->alias([
            'isAdmin' => IsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->context(function (): array {
            $candidate = request()->attributes->get('request_id');

            return is_string($candidate) && Str::isUuid($candidate)
                ? ['request_id' => $candidate]
                : [];
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')
                || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500)
                || $exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException) {
                return null;
            }

            $candidate = $request->attributes->get('request_id');
            $requestId = is_string($candidate) && Str::isUuid($candidate)
                ? $candidate
                : Str::uuid()->toString();
            $request->attributes->set('request_id', $requestId);
            Log::withContext(['request_id' => $requestId]);

            return response()->json([
                'message' => 'Internal server error.',
                'request_id' => $requestId,
            ], 500, ['X-Request-ID' => $requestId]);
        });
    })->create();
