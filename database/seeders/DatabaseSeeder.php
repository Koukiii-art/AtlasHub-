<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or update admin user
        User::firstOrCreate(
            ['email' => 'sweetkouki73@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt(env('ADMIN_PASSWORD', str()->random(32))),
                'is_organizer' => true,
            ]
        );

        // Create test users if they don't exist
        if (User::where('is_organizer', false)->count() < 5) {
            User::factory(5)->create();
        }

        // Seed events, reviews, and other data
        $this->call([
            EventsTableSeeder::class,
            ReviewsTableSeeder::class,
        ]);
    }
}
