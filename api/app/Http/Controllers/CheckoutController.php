<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $transactionItems = [];

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    return response()->json(['message' => "Stock for {$product->name} is insufficient."], 400);
                }

                $totalAmount += $product->price * $item['quantity'];

                $transactionItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ];
            }

            $transaction = Transaction::create([
                'user_id' => $request->user()->id,
                'total_amount' => $totalAmount,
                'status' => 'PENDING',
            ]);

            foreach ($transactionItems as $item) {
                $item['transaction_id'] = $transaction->id;
                TransactionItem::create($item);
            }

            Configuration::setXenditKey(env('XENDIT_API_KEY'));
            $apiInstance = new InvoiceApi();

            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => 'INV-' . $transaction->id,
                'amount' => $totalAmount,
                'payer_email' => $request->user()->email,
                'description' => 'Invoice for Transaction #' . $transaction->id,
            ]);

            try {
                $result = $apiInstance->createInvoice($createInvoiceRequest);
                
                $transaction->update([
                    'xendit_invoice_id' => $result['id'],
                    'invoice_url' => $result['invoice_url']
                ]);

                return response()->json([
                    'transaction_id' => $transaction->id,
                    'invoice_url' => $result['invoice_url']
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create invoice.', 'error' => $e->getMessage()], 500);
            }
        });
    }
}
