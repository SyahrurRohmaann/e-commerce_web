<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

class ProductController extends Controller
{
    /**
     * Convert an uploaded image to WebP when GD or Imagick is available.
     * Falls back to storing the original file safely when no converter exists.
     */
    private function storeImage($file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        // Strict MIME validation via finfo (server-side, not client extension).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $slug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $random = Str::random(12);

        if (extension_loaded('gd') || extension_loaded('imagick')) {
            try {
                $image = null;
                if (extension_loaded('imagick')) {
                    $image = new \Imagick($file->getRealPath());
                    $image->setImageFormat('webp');
                    $image->setImageCompressionQuality(82);
                    $image->stripImage();
                    $blogData = $image->getImageBlob();
                    $image->clear();
                    $image->destroy();
                } else {
                    $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
                    if ($image !== false) {
                        ob_start();
                        imagewebp($image, null, 82);
                        $blogData = ob_get_clean();
                        imagedestroy($image);
                    }
                }

                if (isset($blogData) && $blogData !== false && $blogData !== '') {
                    $path = "products/{$slug}-{$random}.webp";
                    Storage::disk('public')->put($path, $blogData);

                    return $path;
                }
            } catch (\Throwable $e) {
                // Fall through to safe fallback below.
            }
        }

        // Fallback: store the original bytes with its real extension.
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
        $path = "products/{$slug}-{$random}.{$extension}";
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function fileRules(): array
    {
        return [
            'file' => 'nullable|file|image|max:4096',
        ];
    }

    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 100) ?: 10;

        return response()->json(Product::with('category.parent')->orderBy('created_at', 'desc')->paginate($perPage));
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
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'hover_image' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        $imageUrl = $validated['image_url'] ?? null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'));
            if ($imagePath) {
                $imageUrl = Storage::disk('public')->url($imagePath);
            }
        }

        $hoverImageUrl = $validated['hover_image_url'] ?? null;
        if ($request->hasFile('hover_image')) {
            $hoverPath = $this->storeImage($request->file('hover_image'));
            if ($hoverPath) {
                $hoverImageUrl = Storage::disk('public')->url($hoverPath);
            }
        }

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'image_url' => $imageUrl,
            'hover_image_url' => $hoverImageUrl,
        ]);

        return response()->json(['data' => $product, 'message' => 'Product created successfully'], 201);
    }

    public function show(Product $product)
    {
        return response()->json(['data' => $product->load('category.parent')]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image_url' => ['nullable', 'url', 'starts_with:https://'],
            'hover_image_url' => ['nullable', 'url', 'starts_with:https://'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'hover_image' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        $imageUrl = $validated['image_url'] ?? $product->image_url;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'));
            if ($imagePath) {
                $imageUrl = Storage::disk('public')->url($imagePath);

                // Clean up the previous stored file when it was an uploaded one.
                $this->deleteStoredFile($product->image_url);
            }
        }

        $hoverImageUrl = $validated['hover_image_url'] ?? $product->hover_image_url;
        if ($request->hasFile('hover_image')) {
            $hoverPath = $this->storeImage($request->file('hover_image'));
            if ($hoverPath) {
                $hoverImageUrl = Storage::disk('public')->url($hoverPath);

                $this->deleteStoredFile($product->hover_image_url);
            }
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $product->description,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'image_url' => $imageUrl,
            'hover_image_url' => $hoverImageUrl,
        ]);

        return response()->json(['data' => $product, 'message' => 'Product updated successfully']);
    }

    public function destroy(Product $product)
    {
        $this->deleteStoredFile($product->image_url);
        $this->deleteStoredFile($product->hover_image_url);

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    private function deleteStoredFile(?string $url): void
    {
        if (!$url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return;
        }

        $relative = ltrim(str_replace('/storage/', '', $path), '/');
        if ($relative === '' || $relative === $path) {
            return;
        }

        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}