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

        $transaction->update(['shipping_status' => $request->shipping_status]);
        return response()->json(['data' => $transaction, 'message' => 'Status updated']);
    }
}

