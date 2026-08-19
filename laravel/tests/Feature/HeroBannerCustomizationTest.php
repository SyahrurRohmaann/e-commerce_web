<?php

namespace Tests\Feature;

use App\Models\HeroBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroBannerCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_split_editorial_banner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/hero-banners', [
            'title' => 'Form, without noise.',
            'caption' => 'The new collection',
            'subtitle' => 'Clean lines, honest materials, lasting character.',
            'image_url' => 'https://example.com/hero.jpg',
            'button_text' => 'View the edit',
            'button_url' => '/#collection',
            'layout_direction' => 'text-left',
            'panel_theme' => 'stone',
            'image_position' => '65% 40%',
            'text_alignment' => 'left',
            'duration_ms' => 6000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.layout_direction', 'text-left')
            ->assertJsonPath('data.panel_theme', 'stone')
            ->assertJsonPath('data.image_position', '65% 40%')
            ->assertJsonPath('data.text_alignment', 'left');

        $this->assertDatabaseHas('hero_banners', [
            'title' => 'Form, without noise.',
            'layout_direction' => 'text-left',
            'panel_theme' => 'stone',
            'image_position' => '65% 40%',
            'text_alignment' => 'left',
        ]);
    }

    public function test_split_editorial_options_are_constrained(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/hero-banners', [
            'title' => str_repeat('A', 56),
            'subtitle' => str_repeat('B', 161),
            'image_url' => 'https://example.com/hero.jpg',
            'layout_direction' => 'diagonal',
            'panel_theme' => 'rainbow',
            'image_position' => 'outside',
            'text_alignment' => 'justify',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'subtitle',
                'layout_direction',
                'panel_theme',
                'image_position',
                'text_alignment',
            ]);
    }

    public function test_public_endpoint_returns_only_active_banners_in_order(): void
    {
        HeroBanner::create([
            'title' => 'Second',
            'image_url' => 'https://example.com/second.jpg',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        HeroBanner::create([
            'title' => 'Hidden',
            'image_url' => 'https://example.com/hidden.jpg',
            'sort_order' => 1,
            'is_active' => false,
        ]);
        HeroBanner::create([
            'title' => 'First',
            'image_url' => 'https://example.com/first.jpg',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/hero-banners');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'First')
            ->assertJsonPath('data.1.title', 'Second');
    }
}
