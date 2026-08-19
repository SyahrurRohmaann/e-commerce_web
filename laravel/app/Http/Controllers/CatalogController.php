<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category.parent');

        if ($request->filled('search')) {
            $term = trim($request->input('search'));

            if ($term !== '') {
                $query->where(function ($builder) use ($term) {
                    $builder->where('name', 'like', '%' . $term . '%')
                        ->orWhereHas('category', function ($categoryQuery) use ($term) {
                            $categoryQuery->where('name', 'like', '%' . $term . '%');
                        });
                });
            }
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $categoryIds = Category::descendantIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        }

        return response()->json(['data' => $query->get()]);
    }
}