<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Booking;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Event::query();

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter by search term
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by location
        if ($request->has('location') && $request->location) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('date', $request->date);
        }

        // Upcoming events only (date >= today)
        if ($request->has('upcoming') && $request->upcoming) {
            $query->whereDate('date', '>=', now()->toDateString());
        }

        // Order by date
        $query->orderBy('date', 'asc');

        $events = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $events,
        ], 200);
    }

    /**
     * Store a newly created event.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'numberOfPlaces' => 'required|integer|min:1',
            'category' => 'required|string',
            'location' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('events', $imageName, 'public');
            $validated['image'] = Storage::url($imagePath);
        }

        // Create the event
        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'time' => $request->input('time') ?? null,
            'price' => $validated['price'],
            'numberOfPlaces' => $validated['numberOfPlaces'],
            'category' => $validated['category'],
            'location' => $validated['location'],
            'image' => $validated['image'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        // Send notification to the event creator (management type)
        Notification::create([
            'user_id'     => $request->user()->id,
            'event_id'    => $event->id,
            'type'        => 'management',
            'title'       => 'Event published: "' . $event->title . '"',
            'message'     => 'Your event "' . $event->title . '" has been published successfully.',
            'event_image' => $event->image,
            'is_read'     => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event,
        ], 201);
    }

    /**
     * Display the specified event.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $event = Event::with(['user', 'reviews.user'])->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event,
        ], 200);
    }

    /**
     * Update the specified event.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Check if user owns the event
        if ($event->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this event',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'date' => 'sometimes|date|after_or_equal:today',
            'time' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'numberOfPlaces' => 'sometimes|integer|min:1',
            'category' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image) {
                $oldImagePath = str_replace('/storage/', '', $event->image);
                Storage::disk('public')->delete($oldImagePath);
            }
            
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('events', $imageName, 'public');
            $validated['image'] = Storage::url($imagePath);
        }

        $event->update($validated);

        // Management notification: changes saved
        Notification::create([
            'user_id'     => $request->user()->id,
            'event_id'    => $event->id,
            'type'        => 'management',
            'title'       => 'Changes saved for "' . $event->title . '"',
            'message'     => 'Changes to "' . $event->title . '" have been saved successfully.',
            'event_image' => $event->image,
            'is_read'     => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event,
        ], 200);
    }

    /**
     * Remove the specified event.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Check if user owns the event
        if ($event->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this event',
            ], 403);
        }

        // Delete image if exists
        if ($event->image) {
            $imagePath = str_replace('/storage/', '', $event->image);
            Storage::disk('public')->delete($imagePath);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
        ], 200);
    }

    /**
     * Display events created by authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myEvents(Request $request)
    {
        $events = Event::where('user_id', $request->user()->id)
            ->orderBy('date', 'asc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $events,
        ], 200);
    }

    /**
     * Book an event.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function book(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Check if event has available places
        $bookedCount = Booking::where('event_id', $id)->count();
        $availablePlaces = $event->numberOfPlaces - $bookedCount;

        if ($availablePlaces <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No available places for this event',
            ], 400);
        }

        // Check if user already booked this event
        $existingBooking = Booking::where('event_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You have already booked this event',
            ], 400);
        }

        // Create booking
        $booking = Booking::create([
            'event_id' => $id,
            'user_id' => $request->user()->id,
            'status' => 'confirmed',
            'booking_date' => now(),
        ]);

        // ── Notify the user who booked ────────────────────────────────────────
        Notification::create([
            'user_id'     => $request->user()->id,
            'event_id'    => $event->id,
            'type'        => 'booking',
            'title'       => "Booking confirmed for {$event->title}",
            'message'     => "You have successfully booked {$event->title}. Your ticket is confirmed.",
            'event_image' => $event->image,
            'is_read'     => false,
        ]);

        // ── Notify the event organizer ────────────────────────────────────────
        $organizerId = $event->user_id;
        if ($organizerId && $organizerId !== $request->user()->id) {

            $newBookedCount = $bookedCount + 1; // after this booking
            $capacity       = $event->numberOfPlaces ?: 1;
            $fillPct        = round(($newBookedCount / $capacity) * 100);

            // 1. New booking notification
            Notification::create([
                'user_id'     => $organizerId,
                'event_id'    => $event->id,
                'type'        => 'booking',
                'title'       => "New booking for {$event->title}",
                'message'     => "You have a new booking for your event. Booked: {$newBookedCount} / {$capacity}",
                'event_image' => $event->image,
                'is_read'     => false,
            ]);

            // 2. Milestones: 50%, 95%, 100% full
            if ($fillPct >= 100 && $bookedCount < $capacity) {
                Notification::create([
                    'user_id'     => $organizerId,
                    'event_id'    => $event->id,
                    'type'        => 'event_status',
                    'title'       => "{$event->title} is sold out!",
                    'message'     => 'All available tickets have been booked.',
                    'event_image' => $event->image,
                    'is_read'     => false,
                ]);
            } elseif ($fillPct >= 95 && $fillPct < 100) {
                // Only fire once (check prev pct was below 95)
                $prevPct = round((($newBookedCount - 1) / $capacity) * 100);
                if ($prevPct < 95) {
                    Notification::create([
                        'user_id'     => $organizerId,
                        'event_id'    => $event->id,
                        'type'        => 'event_status',
                        'title'       => "{$event->title} is almost sold out.",
                        'message'     => "95% of available seats have been booked.",
                        'event_image' => $event->image,
                        'is_read'     => false,
                    ]);
                }
            } elseif ($fillPct >= 50 && $fillPct < 95) {
                $prevPct = round((($newBookedCount - 1) / $capacity) * 100);
                if ($prevPct < 50) {
                    Notification::create([
                        'user_id'     => $organizerId,
                        'event_id'    => $event->id,
                        'type'        => 'event_status',
                        'title'       => "{$event->title} is now 50% full.",
                        'message'     => "Booked: {$newBookedCount} / {$capacity}",
                        'event_image' => $event->image,
                        'is_read'     => false,
                    ]);
                }
            }

            // 3. Every 15 bookings milestone
            if ($newBookedCount % 15 === 0) {
                Notification::create([
                    'user_id'     => $organizerId,
                    'event_id'    => $event->id,
                    'type'        => 'booking',
                    'title'       => "Congratulations! {$newBookedCount} tickets sold for {$event->title}.",
                    'message'     => "Your event has received {$newBookedCount} bookings.",
                    'event_image' => $event->image,
                    'is_read'     => false,
                ]);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json([
            'success' => true,
            'message' => 'Event booked successfully',
            'data' => $booking,
        ], 201);
    }

    /**
     * Get user's bookings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myBookings(Request $request)
    {
        $bookings = Booking::with('event')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ], 200);
    }

    /**
     * Cancel a booking.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelBooking(Request $request, $id)
    {
        $booking = \App\Models\Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already cancelled',
            ], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $booking,
        ], 200);
    }

    /**
     * Update booking quantity.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $booking = \App\Models\Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update quantity of a cancelled booking',
            ], 400);
        }

        $booking->quantity = $request->quantity;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking quantity updated successfully',
            'data' => $booking,
        ], 200);
    }
}