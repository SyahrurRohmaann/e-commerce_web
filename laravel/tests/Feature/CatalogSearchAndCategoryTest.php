<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $name, ?int $categoryId = null): Product
    {
        return Product::create([
            'name' => $name,
            'category_id' => $categoryId ?? Category::create(['name' => 'Default'])->id,
            'price' => 100000,
            'stock' => 10,
        ]);
    }

    public function test_search_filters_products_by_name_case_insensitive(): void
    {
        $this->makeProduct('Silk Evening Dress');
        $this->makeProduct('Cotton Shirt');

        $response = $this->getJson('/api/catalog?search=dress');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Silk Evening Dress', $response->json('data.0.name'));
    }

    public function test_search_filters_products_by_category_name(): void
    {
        $category = Category::create(['name' => 'Dresses']);
        $this->makeProduct('Midi Gown', $category->id);
        $this->makeProduct('Plain Tee');

        $response = $this->getJson('/api/catalog?search=Dresses');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Midi Gown', $response->json('data.0.name'));
    }

    public function test_category_filter_includes_subcategory_products(): void
    {
        $parent = Category::create(['name' => 'Women']);
        $child = Category::create(['name' => 'Dresses', 'parent_id' => $parent->id]);

        $this->makeProduct('Parent Item', $parent->id);
        $this->makeProduct('Child Item', $child->id);

        $response = $this->getJson('/api/catalog?category_id=' . $parent->id);

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_public_categories_returns_tree(): void
    {
        $parent = Category::create(['name' => 'Women']);
        Category::create(['name' => 'Dresses', 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $this->assertSame('Women', $response->json('data.0.name'));
        $this->assertSame('Dresses', $response->json('data.0.children.0.name'));
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $parent = Category::create(['name' => 'Women']);
        Category::create(['name' => 'Dresses', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->deleteJson("/api/admin/categories/{$parent->id}")->assertStatus(409);
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }
}