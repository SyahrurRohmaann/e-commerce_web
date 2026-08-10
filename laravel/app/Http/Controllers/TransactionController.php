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
            'shipping_status' => 'required|in:pending,shipping,arrive'
        ]);

        if ($request->shipping_status === 'shipping') {
            if ($transaction->status !== 'PAID') {
                return response()->json(['message' => 'Pesanan belum dibayar.'], 400);
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
            
            $transaction->update([
                'shipping_status' => 'shipping',
                'shipping_method' => $request->shipping_method,
                'shipping_courier' => $request->shipping_courier,
                'tracking_number' => $request->tracking_number,
            ]);
        } else {
            $transaction->update(['shipping_status' => $request->shipping_status]);
        }

        return response()->json(['data' => $transaction, 'message' => 'Status updated']);
    }
}

