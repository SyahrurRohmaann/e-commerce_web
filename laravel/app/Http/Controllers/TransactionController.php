<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

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
        if (!$token) {
            return response()->json(['message' => 'Tracking token required'], 400);
        }

        $transaction = Transaction::with(['items'])->where('id', $id)->where('tracking_token', $token)->first();
        
        if (!$transaction) {
            return response()->json(['message' => 'Not found'], 404);
        }
        
        $this->syncStatusWithXendit($transaction);

        return response()->json(['data' => $transaction]);
    }

    public function trackGuest(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['message' => 'Tracking token required'], 400);
        }

        $transaction = Transaction::with(['items'])->where('tracking_token', $token)->first();
        
        if (!$transaction) {
            return response()->json(['message' => 'Not found'], 404);
        }
        
        $this->syncStatusWithXendit($transaction);

        return response()->json(['data' => $transaction]);
    }

    private function syncStatusWithXendit(Transaction $transaction)
    {
        if ($transaction->status !== 'PENDING' || !$transaction->xendit_invoice_id) {
            return;
        }

        try {
            \Xendit\Configuration::setXenditKey(env('XENDIT_API_KEY'));
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $apiInstance = new \Xendit\Invoice\InvoiceApi($client);
            
            $invoice = $apiInstance->getInvoiceById($transaction->xendit_invoice_id);

            if (isset($invoice['status']) && $invoice['status'] !== 'PENDING') {
                $status = $invoice['status'];
                
                \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $invoice, $status) {
                    $transaction->refresh();
                    if ($transaction->status !== 'PENDING') return;

                    $updateData = ['status' => $status];
                    if (isset($invoice['payment_channel'])) {
                        $updateData['payment_method'] = $invoice['payment_channel'];
                    } elseif (isset($invoice['payment_method'])) {
                        $updateData['payment_method'] = $invoice['payment_method'];
                    }

                    if ($status === 'PAID') {
                        $transaction->update($updateData);
                        foreach ($transaction->items as $item) {
                            if ($item->product_id) {
                                \App\Models\Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                            }
                        }

                        $email = $transaction->user ? $transaction->user->email : $transaction->guest_email;
                        if ($email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\OrderPaymentSuccessMail($transaction));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed sending payment success email via sync: ' . $e->getMessage());
                            }
                        }
                    } elseif ($status === 'EXPIRED') {
                        $transaction->update($updateData);
                    }
                });
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync Xendit status: ' . $e->getMessage());
        }
    }
    
    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'sometimes|required|in:PENDING,PAID,EXPIRED',
            'shipping_status' => 'sometimes|required|in:pending,shipping,arrive'
        ]);

        $updates = [];

        // Manual Payment Status Update
        if ($request->has('status') && $request->status !== $transaction->status) {
            $updates['status'] = $request->status;
            
            // Kurangi stok jika admin menandai PAID secara manual
            if ($request->status === 'PAID') {
                foreach ($transaction->items as $item) {
                    if ($item->product_id) {
                        \App\Models\Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                    }
                }

                $email = $transaction->user ? $transaction->user->email : $transaction->guest_email;
                if ($email) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\OrderPaymentSuccessMail($transaction));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed sending payment success email via admin update: ' . $e->getMessage());
                    }
                }
            }
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
                    'tracking_number.required' => 'Nomor resi wajib diisi.'
                ]);
                
                $updates['shipping_status'] = 'shipping';
                $updates['shipping_method'] = $request->shipping_method;
                $updates['shipping_courier'] = $request->shipping_courier;
                $updates['tracking_number'] = $request->tracking_number;
            } else {
                $updates['shipping_status'] = $request->shipping_status;
            }
        }

        if (!empty($updates)) {
            $transaction->update($updates);
        }

        return response()->json(['data' => $transaction, 'message' => 'Status updated']);
    }
}

