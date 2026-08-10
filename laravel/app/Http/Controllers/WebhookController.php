<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $callbackToken = $request->header('x-callback-token');

        if ($callbackToken !== env('XENDIT_CALLBACK_TOKEN')) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $payload = $request->all();
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$externalId || !$status) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $transactionId = str_replace('INV-', '', $externalId);

        DB::transaction(function () use ($transactionId, $status) {
            $transaction = Transaction::with('items')
                ->lockForUpdate()
                ->find($transactionId);

            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            if ($transaction->status === 'PAID' || $transaction->status === 'EXPIRED') {
                return response()->json(['message' => 'Transaction already processed']);
            }

            if ($status === 'PAID') {
                $transaction->update(['status' => 'PAID']);
                
                foreach ($transaction->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                    }
                }
            } elseif ($status === 'EXPIRED') {
                $transaction->update(['status' => 'EXPIRED']);
            }
        });

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
