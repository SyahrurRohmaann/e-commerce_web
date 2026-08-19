<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PaymentTransitionService;
use App\Support\CanonicalAmount;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 15), 100) ?: 15;

        return response()->json(Transaction::with(['user'])->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function userTransactions(Request $request)
    {
        $transactions = Transaction::with(['items'])->where('user_id', $request->user()->id)->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $transactions]);
    }

    public function showUser(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->syncStatusWithXendit($transaction);

        return response()->json(['data' => $transaction->load(['items'])]);
    }

    public function show(Transaction $transaction)
    {
        $this->syncStatusWithXendit($transaction);

        return response()->json(['data' => $transaction->load(['user', 'items'])]);
    }

    public function showGuest(Request $request, $id)
    {
        $token = $request->query('token');
        if (! $token) {
            return response()->json(['message' => 'Tracking token required'], 400);
        }

        $transaction = Transaction::with(['items'])->where('id', $id)->where('tracking_token', $token)->first();

        if (! $transaction) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->syncStatusWithXendit($transaction);

        return response()->json(['data' => $transaction]);
    }

    public function trackGuest(Request $request)
    {
        $token = $request->query('token');
        if (! $token) {
            return response()->json(['message' => 'Tracking token required'], 400);
        }

        $transaction = Transaction::with(['items'])->where('tracking_token', $token)->first();

        if (! $transaction) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->syncStatusWithXendit($transaction);

        return response()->json(['data' => $transaction]);
    }

    private function syncStatusWithXendit(Transaction $transaction)
    {
        if ($transaction->status !== 'PENDING' || ! $transaction->xendit_invoice_id) {
            return;
        }

        try {
            Configuration::setXenditKey(config('services.xendit.api_key'));
            $client = new Client;
            $apiInstance = new InvoiceApi($client);

            $invoice = $apiInstance->getInvoiceById($transaction->xendit_invoice_id);

            if (isset($invoice['status']) && in_array($invoice['status'], ['PAID', 'SETTLED', 'EXPIRED'], true)
                && isset($invoice['id'], $invoice['external_id'], $invoice['amount'])
                && hash_equals((string) $transaction->xendit_invoice_id, (string) $invoice['id'])
                && hash_equals('INV-'.$transaction->id, (string) $invoice['external_id'])
                && CanonicalAmount::equals((string) $transaction->total_amount, $invoice['amount'])) {
                $status = $invoice['status'] === 'SETTLED' ? 'PAID' : $invoice['status'];
                app(PaymentTransitionService::class)->transition(
                    $transaction->id,
                    $status,
                    $invoice['payment_channel'] ?? $invoice['payment_method'] ?? null,
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync Xendit status.', ['exception' => $e::class]);
        }
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'sometimes|required|in:PENDING,PAID,EXPIRED',
            'shipping_status' => 'sometimes|required|in:pending,shipping,arrive',
        ]);

        $updates = [];

        // Manual Payment Status Update
        if ($request->has('status') && $request->status !== $transaction->status) {
            try {
                $result = app(PaymentTransitionService::class)->transition($transaction->id, $request->status);
            } catch (\RuntimeException) {
                return response()->json(['message' => 'Payment status transition conflict'], 409);
            }

            if (! $result) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            $transaction->refresh();
        }

        // Shipping Status Update
        if ($request->has('shipping_status')) {
            if ($request->shipping_status === 'shipping') {
                $checkStatus = $request->has('status') ? $request->status : $transaction->status;
                if ($checkStatus !== 'PAID') {
                    return response()->json(['message' => 'Pesanan belum dibayar. Tidak bisa ubah ke status shipping.'], 400);
                }

                $request->validate([
                    'shipping_method' => 'required|string',
                    'shipping_courier' => 'required|string',
                    'tracking_number' => 'required|string',
                ], [
                    'shipping_method.required' => 'Jenis pengiriman wajib diisi.',
                    'shipping_courier.required' => 'Kurir wajib diisi.',
                    'tracking_number.required' => 'Nomor resi wajib diisi.',
                ]);

                $updates['shipping_status'] = 'shipping';
                $updates['shipping_method'] = $request->shipping_method;
                $updates['shipping_courier'] = $request->shipping_courier;
                $updates['tracking_number'] = $request->tracking_number;
            } else {
                $updates['shipping_status'] = $request->shipping_status;
            }
        }

        if (! empty($updates)) {
            $transaction->update($updates);
        }

        return response()->json(['data' => $transaction, 'message' => 'Status updated']);
    }
}
