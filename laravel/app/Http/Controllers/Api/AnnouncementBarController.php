<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementBar;
use Illuminate\Http\Request;

class AnnouncementBarController extends Controller
{
    /**
     * Public: return active announcement bars ordered by sort_order.
     */
    public function index()
    {
        $bars = AnnouncementBar::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json(['data' => $bars]);
    }

    /**
     * Admin: list all announcement bars.
     */
    public function adminIndex()
    {
        $bars = AnnouncementBar::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        return response()->json(['data' => $bars]);
    }

    private function rules(): array
    {
        return [
            'message' => 'required|string|max:255',
            'background_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        if (!isset($validated['background_color'])) {
            $validated['background_color'] = '#111111';
        }
        if (!isset($validated['text_color'])) {
            $validated['text_color'] = '#FFFFFF';
        }
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = (AnnouncementBar::max('sort_order') ?? 0) + 1;
        }

        $bar = AnnouncementBar::create($validated);

        return response()->json(['data' => $bar], 201);
    }

    public function update(Request $request, int $id)
    {
        $bar = AnnouncementBar::findOrFail($id);
        $validated = $request->validate($this->rules());

        $bar->update($validated);

        return response()->json(['data' => $bar]);
    }

    public function destroy(int $id)
    {
        $bar = AnnouncementBar::findOrFail($id);
        $bar->delete();

        return response()->json(['message' => 'Announcement bar deleted successfully']);
    }
}