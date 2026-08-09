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
            
            // Shipping fields
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_postal_code' => 'required|string',
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
            
            $shippingCost = 25000; // Mock shipping cost
            $totalAmount += $shippingCost;

            // If user is logged in, optionally update their profile
            $user = auth('sanctum')->user();
            if ($user && $request->has('save_address_to_profile') && $request->save_address_to_profile) {
                $user->update([
                    'phone' => $request->customer_phone,
                    'address' => $request->shipping_address,
                    'city' => $request->shipping_city,
                    'postal_code' => $request->shipping_postal_code,
                ]);
            }

            $trackingToken = null;
            if (!$user) {
                $trackingToken = \Illuminate\Support\Str::uuid()->toString();
            }

            $transaction = Transaction::create([
                'user_id' => $user ? $user->id : null,
                'tracking_token' => $trackingToken,
                'total_amount' => $totalAmount,
                'status' => 'PENDING',
                
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_cost' => $shippingCost,
                'shipping_status' => 'pending',
            ]);

            foreach ($transactionItems as $item) {
                $item['transaction_id'] = $transaction->id;
                TransactionItem::create($item);
            }

            Configuration::setXenditKey(env('XENDIT_API_KEY'));
            
            // Bypass SSL for local development
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $apiInstance = new InvoiceApi($client);

            // Use provided email or fallback
            $payerEmail = $user ? $user->email : ($request->customer_email ?? 'guest@example.com');

            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => 'INV-' . $transaction->id,
                'amount' => $totalAmount,
                'payer_email' => $payerEmail,
                'description' => 'Invoice for Transaction #' . $transaction->id,
                'success_redirect_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/checkout/success',
                'failure_redirect_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/checkout/failure',
            ]);

            try {
                $result = $apiInstance->createInvoice($createInvoiceRequest);
                
                $transaction->update([
                    'xendit_invoice_id' => $result['id'],
                    'invoice_url' => $result['invoice_url']
                ]);

                return response()->json([
                    'transaction_id' => $transaction->id,
                    'invoice_url' => $result['invoice_url'],
                    'tracking_token' => $trackingToken
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create invoice.', 'error' => $e->getMessage()], 500);
            }
        });
    }
}

