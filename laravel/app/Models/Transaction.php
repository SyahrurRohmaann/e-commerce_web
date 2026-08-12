<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 
        'tracking_token',
        'xendit_invoice_id', 
        'invoice_url', 
        'total_amount', 
        'status',
        'payment_method',
        'customer_name',
        'customer_phone',
        'guest_email',
        'shipping_address',
        'shipping_country',
        'shipping_province',
        'shipping_city',
        'shipping_sub_district',
        'shipping_postal_code',
        'shipping_cost',
        'shipping_status',
        'shipping_method',
        'shipping_courier',
        'tracking_number'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}

