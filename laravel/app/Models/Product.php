<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['category_id', 'name', 'description', 'price', 'stock', 'image_url', 'hover_image_url'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
