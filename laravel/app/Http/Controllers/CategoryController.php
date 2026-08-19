<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children.children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($validated);

        return response()->json(['data' => $category, 'message' => 'Category created successfully'], 201);
    }

    public function show(Category $category)
    {
        return response()->json(['data' => $category->load('children.children')]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
        ]);

        // Guard against assigning a descendant as the new parent (would create a cycle).
        if ($validated['parent_id'] ?? null) {
            $descendants = Category::descendantIds($category->id);
            if (in_array((int) $validated['parent_id'], $descendants, true)) {
                return response()->json(['message' => 'A category cannot be its own descendant.'], 422);
            }
        }

        $category->update($validated);

        return response()->json(['data' => $category, 'message' => 'Category updated successfully']);
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return response()->json(['message' => 'Cannot delete a category that has subcategories.'], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}