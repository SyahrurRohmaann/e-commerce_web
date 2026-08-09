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
}
