<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin'
        ]);
        
        \App\Models\User::create([
            'name' => 'Customer User',
            'email' => 'customer@customer.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'customer'
        ]);
    }
}
