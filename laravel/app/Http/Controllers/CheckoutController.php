<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',

            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'shipping_address' => 'required|string',
            'shipping_country' => 'nullable|string',
            'shipping_province' => 'nullable|string',
            'shipping_city' => 'required|string',
            'shipping_sub_district' => 'nullable|string',
            'shipping_postal_code' => 'required|string',
        ]);

        $user = auth('sanctum')->user();

        if (! $user) {
            $request->validate(['guest_email' => 'required|email']);
        }

        return DB::transaction(function () use ($request, $user) {
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

            $shippingCost = 25000;
            $totalAmount += $shippingCost;

            if ($user) {
                // Update user profile with the latest checkout details
                $user->update([
                    'phone' => $request->customer_phone,
                    'address' => $request->shipping_address,
                    'country' => $request->shipping_country,
                    'province' => $request->shipping_province,
                    'city' => $request->shipping_city,
                    'sub_district' => $request->shipping_sub_district,
                    'postal_code' => $request->shipping_postal_code,
                ]);
            }

            $trackingToken = $user ? null : Str::uuid()->toString();

            $transaction = Transaction::create([
                'user_id' => $user ? $user->id : null,
                'tracking_token' => $trackingToken,
                'total_amount' => $totalAmount,
                'status' => 'PENDING',

                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'guest_email' => $user ? null : $request->guest_email,
                'shipping_address' => $request->shipping_address,
                'shipping_country' => $request->shipping_country,
                'shipping_province' => $request->shipping_province,
                'shipping_city' => $request->shipping_city,
                'shipping_sub_district' => $request->shipping_sub_district,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_cost' => $shippingCost,
                'shipping_status' => 'pending',
            ]);

            foreach ($transactionItems as $item) {
                $item['transaction_id'] = $transaction->id;
                TransactionItem::create($item);
            }

            Configuration::setXenditKey(config('services.xendit.api_key'));

            $client = new Client;
            $apiInstance = new InvoiceApi($client);

            $payerEmail = $user ? $user->email : $request->guest_email;

            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => 'INV-'.$transaction->id,
                'amount' => $totalAmount,
                'payer_email' => $payerEmail,
                'description' => 'Invoice for Transaction #'.$transaction->id,
                'success_redirect_url' => env('FRONTEND_URL', 'http://localhost:5173').'/checkout/success',
                'failure_redirect_url' => env('FRONTEND_URL', 'http://localhost:5173').'/checkout/failure',
            ]);

            $result = $apiInstance->createInvoice($createInvoiceRequest);

            $transaction->update([
                'xendit_invoice_id' => $result['id'],
                'invoice_url' => $result['invoice_url'],
            ]);

            return response()->json([
                'transaction_id' => $transaction->id,
                'invoice_url' => $result['invoice_url'],
                'tracking_token' => $trackingToken,
            ]);
        });
    }
}
