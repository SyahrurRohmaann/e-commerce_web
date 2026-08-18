<?php

namespace App\Services;

use App\Models\PaymentOutboxEvent;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentTransitionService
{
    public function transition(int $transactionId, string $status, ?string $paymentMethod = null): ?Transaction
    {
        return DB::transaction(function () use ($transactionId, $status, $paymentMethod) {
            $transaction = Transaction::with(['items', 'user'])->lockForUpdate()->find($transactionId);
            if (! $transaction) {
                return null;
            }

            if ($transaction->payment_processed_at || $transaction->status === 'PAID') {
                if ($status !== 'PAID') {
                    throw new RuntimeException('Terminal payment state cannot transition.');
                }

                return $transaction;
            }

            if ($transaction->status === 'EXPIRED') {
                if ($status !== 'EXPIRED') {
                    throw new RuntimeException('Terminal payment state cannot transition.');
                }

                return $transaction;
            }

            if ($status === 'EXPIRED') {
                $transaction->update(array_filter([
                    'status' => 'EXPIRED',
                    'payment_method' => $paymentMethod,
                ], fn ($value) => $value !== null));

                return $transaction->fresh();
            }

            $quantities = $transaction->items
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn ($items) => (int) $items->sum('quantity'))
                ->sortKeys(SORT_NUMERIC);

            $products = Product::query()
                ->whereKey($quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if (! $product || $product->stock < $quantity) {
                    throw new RuntimeException('Insufficient stock while processing payment.');
                }

                $product->decrement('stock', $quantity);
            }

            $transaction->update(array_filter([
                'status' => 'PAID',
                'payment_method' => $paymentMethod,
                'payment_processed_at' => now(),
            ], fn ($value) => $value !== null));
            $email = $transaction->user?->email ?: $transaction->guest_email;
            if ($email) {
                PaymentOutboxEvent::firstOrCreate(
                    ['idempotency_key' => "payment-success:{$transaction->id}"],
                    [
                        'transaction_id' => $transaction->id,
                        'recipient' => $email,
                    ],
                );
            }

            return $transaction->fresh(['items', 'user']);
        }, 5);
    }
}
