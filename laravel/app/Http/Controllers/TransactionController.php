<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Transaction::with(['user'])->get()]);
    }

    public function show(Transaction $transaction)
    {
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

        return response()->json(['data' => $transaction]);
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

