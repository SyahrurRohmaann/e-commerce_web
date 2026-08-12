<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HeroBanner;

class HeroBannerSeeder extends Seeder
{
    public function run(): void
    {
        HeroBanner::insert([
            [
                'title' => 'NEW COLLECTION',
                'caption' => 'Spring/Summer 2026',
                'subtitle' => 'Discover our latest arrivals designed for movement',
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&q=80&w=1600&h=900',
                'title_position' => 'tc',
                'caption_position' => 'tc',
                'button_position' => 'bc',
                'button_text' => 'Shop Now',
                'button_url' => '/catalog',
                'sort_order' => 1,
                'is_active' => true,
                'duration_ms' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'SUMMER SALE',
                'caption' => 'Limited Time Offer',
                'subtitle' => 'Up to 50% off selected items',
                'image_url' => 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&q=80&w=1600&h=900',
                'title_position' => 'tl',
                'caption_position' => 'tl',
                'button_position' => 'bl',
                'button_text' => 'View Deals',
                'button_url' => '/sale',
                'sort_order' => 2,
                'is_active' => true,
                'duration_ms' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'PREMIUM QUALITY',
                'caption' => 'Crafted with Precision',
                'subtitle' => 'Experience luxury in every detail',
                'image_url' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&q=80&w=1600&h=900',
                'title_position' => 'br',
                'caption_position' => 'br',
                'button_position' => 'bc',
                'button_text' => 'Learn More',
                'button_url' => '/about',
                'sort_order' => 3,
                'is_active' => true,
                'duration_ms' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
