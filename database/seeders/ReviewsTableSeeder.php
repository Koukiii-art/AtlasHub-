<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('reviews')->insert([
            [
                'name' => 'Youssef Benali',
                'role' => 'Tech Entrepreneur, Casablanca',
                'avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',
                'stars' => 5,
                'text' => 'Excellent platform for discovering professional events! I found GITEX through Atlasevents and it was a game-changer for my business.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fatima Zahra El Mansouri',
                'role' => 'Marketing Director, Marrakech',
                'avatar' => 'https://randomuser.me/api/portraits/women/68.jpg',
                'stars' => 5,
                'text' => 'As a marketing professional, I attend many conferences. Atlasevents has become my go-to platform for discovering relevant events.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Karim Idrissi',
                'role' => 'Startup Founder, Rabat',
                'avatar' => 'https://randomuser.me/api/portraits/men/45.jpg',
                'stars' => 5,
                'text' => 'From tech summits to industry meetups, I\'ve discovered so many valuable events here. Highly recommended!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}