<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOutboxEvent extends Model
{
    protected $fillable = [
        'transaction_id',
        'idempotency_key',
        'recipient',
        'attempts',
        'last_attempt_at',
        'claim_token',
        'locked_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'locked_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
