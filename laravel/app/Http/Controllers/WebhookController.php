<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PaymentTransitionService;
use App\Support\CanonicalAmount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WebhookController extends Controller
{
    public function handle(Request $request, PaymentTransitionService $payments): JsonResponse
    {
        $expectedToken = config('services.xendit.callback_token');
        $callbackToken = $request->header('x-callback-token');

        if (! is_string($expectedToken) || $expectedToken === '') {
            return response()->json(['message' => 'Webhook unavailable'], 503);
        }

        if (! is_string($callbackToken) || ! hash_equals($expectedToken, $callbackToken)) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $payload = $request->validate([
            'id' => ['required', 'string'],
            'external_id' => ['required', 'string', 'regex:/^INV-[1-9][0-9]*$/'],
            'status' => ['required', 'string', 'in:PAID,SETTLED,EXPIRED'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_channel' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $transactionId = (int) substr($payload['external_id'], 4);
        $transaction = Transaction::find($transactionId);

        if (! $transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if (! hash_equals("INV-{$transaction->id}", $payload['external_id'])
            || ! is_string($transaction->xendit_invoice_id)
            || ! hash_equals($transaction->xendit_invoice_id, $payload['id'])
            || ! CanonicalAmount::equals((string) $transaction->total_amount, $payload['amount'])) {
            return response()->json(['message' => 'Invoice identity mismatch'], 422);
        }

        try {
            $result = $payments->transition(
                $transaction->id,
                $payload['status'] === 'SETTLED' ? 'PAID' : $payload['status'],
                $payload['payment_channel'] ?? $payload['payment_method'] ?? null,
            );
            if (! $result) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }
        } catch (RuntimeException) {
            return response()->json(['message' => 'Payment transition conflict'], 409);
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
