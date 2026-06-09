<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::firstOrCreate(
            ['email' => 'sweetkouki73@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt(env('ADMIN_PASSWORD', str()->random(32))),
                'is_organizer' => true,
            ]
        );

        $events = [
            [
                'title' => 'Africa CEO Forum 2026',
                'date' => '2026-09-16',
                'time' => '09:00 - 18:00',
                'location' => 'Casablanca, Morocco',
                'category' => 'Business',
                'image' => 'https://images.unsplash.com/photo-1591115765373-5207764f72e7?w=600&h=400&fit=crop',
                'price' => 299,
                'numberOfPlaces' => 500,
                'description' => 'A major business gathering for founders, investors, and public-sector leaders.',
            ],
            [
                'title' => 'Morocco Digital Summit',
                'date' => '2026-10-05',
                'time' => '09:00 - 18:00',
                'location' => 'Rabat, Morocco',
                'category' => 'Learning',
                'image' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=600&h=400&fit=crop',
                'price' => 199,
                'numberOfPlaces' => 350,
                'description' => 'Technology, AI, cloud, and digital transformation sessions for professionals.',
            ],
            [
                'title' => 'Marrakech Food & Culture Fest',
                'date' => '2026-08-18',
                'time' => '16:00 - 23:00',
                'location' => 'Marrakech, Morocco',
                'category' => 'Community',
                'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&h=400&fit=crop',
                'price' => 0,
                'numberOfPlaces' => 1000,
                'description' => 'Celebrate Moroccan food, music, culture, and community experiences.',
            ],
            [
                'title' => 'Contemporary Art Exhibition',
                'date' => '2026-11-12',
                'time' => '10:00 - 20:00',
                'location' => 'Casablanca, Morocco',
                'category' => 'Art',
                'image' => 'https://images.unsplash.com/photo-1531058020387-3be344556be6?w=600&h=400&fit=crop',
                'price' => 25,
                'numberOfPlaces' => 120,
                'description' => 'Discover contemporary art from emerging local and international artists.',
            ],
            [
                'title' => 'Casablanca Volunteer Day',
                'date' => '2026-07-22',
                'time' => '08:00 - 16:00',
                'location' => 'Casablanca, Morocco',
                'category' => 'Community',
                'image' => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?w=600&h=400&fit=crop',
                'price' => 0,
                'numberOfPlaces' => 800,
                'description' => 'A community day for volunteering, cleanup, and neighborhood initiatives.',
            ],
            [
                'title' => 'Startup Workshop Casablanca',
                'date' => '2026-12-03',
                'time' => '10:00 - 17:00',
                'location' => 'Casablanca, Morocco',
                'category' => 'Workshops',
                'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400&fit=crop',
                'price' => 99,
                'numberOfPlaces' => 150,
                'description' => 'Hands-on workshop for early-stage founders and product builders.',
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                ['title' => $event['title']],
                $event + ['user_id' => $organizer->id]
            );
        }
    }
}
