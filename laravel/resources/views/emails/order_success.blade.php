<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #111; }
        .section { margin: 20px 0; }
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; color: #666; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f9f9f9; }
        .total-row td { font-weight: bold; }
        .token-box { background: #f0f7ff; border: 1px dashed #0066cc; padding: 15px; text-align: center; border-radius: 6px; margin: 20px 0; }
        .token-title { font-size: 12px; text-transform: uppercase; color: #0066cc; font-weight: bold; }
        .token-code { font-family: monospace; font-size: 18px; font-weight: bold; margin: 8px 0; color: #111; }
        .btn { display: inline-block; background: #111; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 4px; font-size: 14px; font-weight: bold; text-transform: uppercase; margin-top: 10px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Successful!</h1>
            <p>Thank you for your order</p>
        </div>

        <div class="section">
            <div class="section-title">Shipping & Contact Information</div>
            <p>
                <strong>Name:</strong> {{ $transaction->customer_name }}<br>
                <strong>Email:</strong> {{ $transaction->user ? $transaction->user->email : $transaction->guest_email }}<br>
                <strong>Phone:</strong> {{ $transaction->customer_phone }}<br>
                <strong>Address:</strong> {{ $transaction->shipping_address }}, {{ $transaction->shipping_city }} - {{ $transaction->shipping_postal_code }}
            </p>
        </div>

        @if($transaction->tracking_token)
        <div class="token-box">
            <div class="token-title">Your Order Tracking Token</div>
            <div class="token-code">{{ $transaction->tracking_token }}</div>
            <a href="{{ $trackUrl }}" class="btn" style="color: #ffffff;">Track Your Order</a>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Order Items</div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" align="right"><strong>Shipping Cost</strong></td>
                        <td>Rp {{ number_format($transaction->shipping_cost ?? 25000, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" align="right"><strong>Grand Total</strong></td>
                        <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(!$transaction->tracking_token)
        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ $trackUrl }}" class="btn" style="color: #ffffff;">View Order History</a>
        </div>
        @endif

        <div class="footer">
            <p>If you have any questions, please reply to this email.<br>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
