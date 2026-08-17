<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroBanner;
use Illuminate\Http\Request;

class HeroBannerController extends Controller
{
    // Public: get active banners sorted by sort_order
    public function index()
    {
        $banners = HeroBanner::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json(['data' => $banners]);
    }

    // Admin: list all banners
    public function adminIndex()
    {
        $banners = HeroBanner::orderBy('sort_order', 'asc')->get();
        return response()->json(['data' => $banners]);
    }

    // Admin: get single banner
    public function show($id)
    {
        $banner = HeroBanner::findOrFail($id);
        return response()->json(['data' => $banner]);
    }

    private function rules(): array
    {
        return [
            'title' => 'nullable|string|max:55',
            'caption' => 'nullable|string|max:80',
            'subtitle' => 'nullable|string|max:160',
            'image_url' => 'required|url',
            'layout_direction' => 'nullable|in:text-left,text-right',
            'panel_theme' => 'nullable|in:ivory,stone,ink',
            'image_position' => ['nullable', 'regex:/^(?:100|[1-9]?\d)% (?:100|[1-9]?\d)%$/'],
            'text_alignment' => 'nullable|in:left,center',
            'button_text' => 'nullable|string|max:40',
            'button_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'duration_ms' => 'nullable|integer|min:1000|max:30000',
        ];
    }

    // Admin: create banner
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        // Auto sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = HeroBanner::max('sort_order') + 1 ?? 1;
        }
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        if (!isset($validated['duration_ms'])) {
            $validated['duration_ms'] = 5000;
        }

        $banner = HeroBanner::create($validated);
        return response()->json(['data' => $banner], 201);
    }

    // Admin: update banner
    public function update(Request $request, $id)
    {
        $banner = HeroBanner::findOrFail($id);

        $validated = $request->validate($this->rules());

        $banner->update($validated);
        return response()->json(['data' => $banner]);
    }

    // Admin: delete banner
    public function destroy($id)
    {
        $banner = HeroBanner::findOrFail($id);
        $banner->delete();
        return response()->json(['message' => 'Banner deleted successfully']);
    }
}
