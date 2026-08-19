<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementBar extends Model
{
    protected $fillable = [
        'message',
        'background_color',
        'text_color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}