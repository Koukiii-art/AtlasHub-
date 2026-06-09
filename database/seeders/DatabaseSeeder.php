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
        $admin = User::firstOrCreate(
            ['email' => 'sweetkouki73@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('admin123'),
                'is_organizer' => true,
            ]
        );

        // Create test users with specific details
        $testUsers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => bcrypt('password123'),
                'is_organizer' => false,
            ],
            [
                'name' => 'Sarah Smith',
                'email' => 'sarah@example.com',
                'password' => bcrypt('password123'),
                'is_organizer' => false,
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'password' => bcrypt('password123'),
                'is_organizer' => true,
            ],
            [
                'name' => 'Emily Wilson',
                'email' => 'emily@example.com',
                'password' => bcrypt('password123'),
                'is_organizer' => false,
            ],
            [
                'name' => 'David Brown',
                'email' => 'david@example.com',
                'password' => bcrypt('password123'),
                'is_organizer' => false,
            ],
        ];

        foreach ($testUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Seed events, reviews, and other data
        $this->call([
            EventsTableSeeder::class,
            ReviewsTableSeeder::class,
        ]);
    }
}
