<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroBanner extends Model
{
    protected $fillable = [
        'title',
        'caption',
        'subtitle',
        'image_url',
        'title_position',
        'caption_position',
        'button_position',
        'button_text',
        'button_url',
        'sort_order',
        'is_active',
        'duration_ms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
