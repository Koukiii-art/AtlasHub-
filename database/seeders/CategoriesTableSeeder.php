<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'All Events', 'slug' => 'all-events', 'icon' => '✨', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Music', 'slug' => 'music', 'icon' => '🎵', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Technology', 'slug' => 'technology', 'icon' => '⚡', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Art', 'slug' => 'art', 'icon' => '🎨', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Food', 'slug' => 'food', 'icon' => '🍔', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Business', 'slug' => 'business', 'icon' => '💼', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}