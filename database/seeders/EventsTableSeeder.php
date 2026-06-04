<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('events')->insert([
            [
                'title' => 'GITEX Global 2025',
                'date' => '2025-10-13',
                'time' => '09:00 - 18:00',
                'location' => 'Dubai World Trade Centre, UAE',
                'category' => 'Technology',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=600&h=400&fit=crop',
                'price' => '$499',
                'description' => 'The world\'s largest tech conference featuring AI, cloud, and digital innovation.',
                'is_featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'AI Revolution Summit',
                'date' => '2025-08-05',
                'time' => '09:00 - 18:00',
                'location' => 'Convention Center, SF',
                'category' => 'Technology',
                'image' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=600&h=400&fit=crop',
                'price' => '$299',
                'description' => 'Where AI meets the future - industry leaders share insights.',
                'is_featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Summer Music Festival',
                'date' => '2025-07-15',
                'time' => '16:00 - 23:00',
                'location' => 'Central Park, NYC',
                'category' => 'Music',
                'image' => 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=600&h=400&fit=crop',
                'price' => '$89',
                'description' => 'Experience the biggest summer festival with top artists.',
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Contemporary Art Exhibition',
                'date' => '2025-10-05',
                'time' => '10:00 - 20:00',
                'location' => 'Art Gallery, NYC',
                'category' => 'Art',
                'image' => 'https://images.unsplash.com/photo-1531058020387-3be344556be6?w=600&h=400&fit=crop',
                'price' => '$25',
                'description' => 'Discover amazing contemporary art from emerging artists.',
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Street Food Festival',
                'date' => '2025-07-25',
                'time' => '11:00 - 22:00',
                'location' => 'Waterfront Park, Miami',
                'category' => 'Food',
                'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&h=400&fit=crop',
                'price' => '$0',
                'description' => 'Free entry! Taste from 100+ gourmet food trucks.',
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Business Leadership Summit',
                'date' => '2025-09-20',
                'time' => '10:00 - 17:00',
                'location' => 'Convention Center, Chicago',
                'category' => 'Business',
                'image' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=600&h=400&fit=crop',
                'price' => '$399',
                'description' => 'Learn from top business leaders and CEOs.',
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}