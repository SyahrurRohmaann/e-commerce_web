<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Xendit Webhook Received', $request->all());

        $callbackToken = $request->header('x-callback-token');
        $expectedToken = env('XENDIT_CALLBACK_TOKEN');

        if ($expectedToken && $callbackToken !== $expectedToken) {
            Log::warning('Xendit Webhook: Invalid Token', ['received' => $callbackToken, 'expected' => $expectedToken]);
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $payload = $request->all();
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$externalId || !$status) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $transactionId = str_replace('INV-', '', $externalId);

        DB::transaction(function () use ($transactionId, $status, $payload) {
            $transaction = Transaction::with('items')
                ->lockForUpdate()
                ->find($transactionId);

            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            if ($transaction->status === 'PAID' || $transaction->status === 'EXPIRED') {
                return response()->json(['message' => 'Transaction already processed']);
            }

            $updateData = ['status' => $status];
            
            // Simpan metode pembayaran (cth: QRIS / CREDIT_CARD)
            if (isset($payload['payment_channel'])) {
                $updateData['payment_method'] = $payload['payment_channel'];
            } elseif (isset($payload['payment_method'])) {
                $updateData['payment_method'] = $payload['payment_method'];
            }

            if ($status === 'PAID') {
                $transaction->update($updateData);
                
                foreach ($transaction->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                    }
                }
            } elseif ($status === 'EXPIRED') {
                $transaction->update($updateData);
            }
        });

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
