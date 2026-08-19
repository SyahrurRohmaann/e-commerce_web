<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 100) ?: 10;

        return response()->json(Product::with('category')->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image_url' => ['nullable', 'url', 'starts_with:https://'],
            'hover_image_url' => ['nullable', 'url', 'starts_with:https://'],
        ]);

        $product = Product::create($validated);
        return response()->json(['data' => $product, 'message' => 'Product created successfully']);
    }

    public function show(Product $product)
    {
        return response()->json(['data' => $product->load('category')]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'image_url' => ['nullable', 'url', 'starts_with:https://'],
            'hover_image_url' => ['nullable', 'url', 'starts_with:https://'],
        ]);

        $product->update($validated);
        return response()->json(['data' => $product, 'message' => 'Product updated successfully']);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
