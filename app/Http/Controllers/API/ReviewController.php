<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user:id,name,profile_picture', 'event:id,title']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('type') && $request->type === 'site') {
            $query->whereNull('event_id');
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }

    public function eventReviews($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        $reviews = Review::with(['user:id,name,profile_picture'])
            ->where('event_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'nullable|exists:events,id',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('event_id', $validated['event_id'] ?? null)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a review for this item.',
            ], 409);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'event_id' => $validated['event_id'] ?? null,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
        ]);

        // If the review is for an event, notify the event creator
        if (isset($validated['event_id'])) {
            $event = \App\Models\Event::find($validated['event_id']);
            if ($event && $event->user_id !== $request->user()->id) {
                $snippet = strlen($validated['comment']) > 60
                    ? '"' . substr($validated['comment'], 0, 60) . '…"'
                    : '"' . $validated['comment'] . '"';
                \App\Models\Notification::create([
                    'user_id'     => $event->user_id,
                    'event_id'    => $event->id,
                    'type'        => 'review',
                    'title'       => 'New review received for "' . $event->title . '".',
                    'message'     => $snippet,
                    'event_image' => $event->image,
                    'is_read'     => false,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Review posted successfully',
            'data' => $review,
        ], 201);
    }

    public function userReviews(Request $request)
    {
        $reviews = Review::with(['user:id,name,profile_picture', 'event:id,title'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }
}
