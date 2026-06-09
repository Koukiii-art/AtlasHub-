<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Seeder;

class ReviewsTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_organizer', false)->get();
        $events = Event::all();

        if ($users->isEmpty() || $events->isEmpty()) {
            return; // Skip if no users or events
        }

        // Create reviews for various events
        $reviews = [
            [
                'user_id' => $users->first()->id ?? 1,
                'event_id' => $events->first()->id ?? 1,
                'rating' => 5,
                'title' => 'Excellent Event!',
                'comment' => 'This was an amazing experience. The speakers were knowledgeable and the organization was perfect.',
            ],
            [
                'user_id' => $users->get(1)?->id ?? 1,
                'event_id' => $events->get(1)?->id ?? 1,
                'rating' => 5,
                'title' => 'Highly Recommended',
                'comment' => 'Great networking opportunities and very professional event. Will attend again!',
            ],
            [
                'user_id' => $users->get(2)?->id ?? 1,
                'event_id' => $events->get(2)?->id ?? 1,
                'rating' => 4,
                'title' => 'Very Good',
                'comment' => 'Well-organized event with good content. Looking forward to the next one.',
            ],
        ];

        foreach ($reviews as $review) {
            Review::create($review);
        }
    }
}