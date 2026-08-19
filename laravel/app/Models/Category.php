<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'parent_id'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    /**
     * Recursively collect this category id and all descendant ids.
     * Depth-safe: uses a bounded loop instead of unbounded recursion.
     */
    public static function descendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $frontier = [$categoryId];
        $guard = 0;

        while ($frontier && $guard < 32) {
            $children = static::whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = $children;
            $ids = array_merge($ids, $children);
            $guard++;
        }

        return array_values(array_unique($ids));
    }
}