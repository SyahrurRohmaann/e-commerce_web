<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [];
        for ($i = 1; $i <= 10; $i++) {
            $products[] = [
                'category_id' => rand(1, 3),
                'name' => 'Product ' . $i,
                'description' => 'Description for product ' . $i,
                'price' => rand(10, 100) * 1000,
                'stock' => rand(10, 100),
                'image_url' => 'https://via.placeholder.com/150',
            ];
        }

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
