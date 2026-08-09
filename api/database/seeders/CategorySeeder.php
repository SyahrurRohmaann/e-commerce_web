<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Gadgets and electronics'],
            ['name' => 'Clothing', 'description' => 'Apparel and fashion'],
            ['name' => 'Books', 'description' => 'Physical and digital books'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
