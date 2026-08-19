<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_stores_image_and_sets_url(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Accessories']);

        $file = UploadedFile::fake()->image('earrings.jpg', 200, 200);

        $response = $this->actingAs($admin)->post('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Gold Earrings',
            'price' => 250000,
            'stock' => 5,
            'image' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $this->assertStringContainsString('/storage/products/', $response->json('data.image_url'));

        $product = Product::first();
        $path = str_replace('/storage/', '', parse_url($product->image_url, PHP_URL_PATH));
        Storage::disk('public')->assertExists($path);

        $stored = Storage::disk('public')->get($path);

        if (extension_loaded('gd') || extension_loaded('imagick')) {
            // Converter available: the stored file must be actual WebP.
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $this->assertSame('image/webp', $finfo->buffer($stored));
        } else {
            // No converter: original bytes stored safely (fallback path).
            $this->assertNotEmpty($stored);
        }
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Accessories']);

        $file = UploadedFile::fake()->create('evil.txt', 100, 'text/plain');

        $response = $this->actingAs($admin)->post('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Bad Upload',
            'price' => 1000,
            'stock' => 1,
            'image' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $this->assertCount(0, Product::all());
    }
}