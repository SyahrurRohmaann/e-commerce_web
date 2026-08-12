<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $trackUrl;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction->load(['items', 'user']);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        if ($transaction->tracking_token) {
            $this->trackUrl = "{$frontendUrl}/track-order?token={$transaction->tracking_token}";
        } else {
            $this->trackUrl = "{$frontendUrl}/profile";
        }
    }

    public function build()
    {
        return $this->subject('[Payment Successful] Invoice & Order Receipt')
                    ->view('emails.order_success');
    }
}
