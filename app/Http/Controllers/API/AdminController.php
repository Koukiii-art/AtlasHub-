<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use App\Models\Review;
use App\Models\OrganizerApplication;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private const ADMIN_EMAIL = 'sweetkouki73@gmail.com';

    private function checkAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->email !== self::ADMIN_EMAIL) {
            abort(403, 'Unauthorized');
        }
        return $user;
    }

    public function stats(Request $request)
    {
        $this->checkAdmin($request);

        $now    = now();
        $last30 = now()->subDays(30);

        $totalUsers      = User::count();
        $prevUsers       = User::where('created_at', '<', $last30)->count();
        $totalOrganizers = User::where('is_organizer', true)->count();
        $totalEvents     = Event::count();
        $prevEvents      = Event::where('created_at', '<', $last30)->count();
        $totalBookings   = Booking::count();
        $prevBookings    = Booking::where('created_at', '<', $last30)->count();
        $totalRevenue    = Booking::join('events', 'bookings.event_id', '=', 'events.id')
                            ->sum('events.price');
        $prevRevenue     = Booking::join('events', 'bookings.event_id', '=', 'events.id')
                            ->where('bookings.created_at', '<', $last30)
                            ->sum('events.price');
        $ticketsSold     = Booking::where('status', 'confirmed')->count();

        $pct = fn($cur, $prev) => $prev > 0 ? round((($cur - $prev) / $prev) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'stats' => [
                'total_users'       => $totalUsers,
                'users_pct'         => $pct($totalUsers, $prevUsers),
                'total_organizers'  => $totalOrganizers,
                'total_events'      => $totalEvents,
                'events_pct'        => $pct($totalEvents, $prevEvents),
                'total_bookings'    => $totalBookings,
                'bookings_pct'      => $pct($totalBookings, $prevBookings),
                'total_revenue'     => round($totalRevenue, 2),
                'revenue_pct'       => $pct($totalRevenue, $prevRevenue),
                'tickets_sold'      => $ticketsSold,
            ],
        ]);
    }

    public function overview(Request $request)
    {
        $this->checkAdmin($request);

        // Last 7 days user signups, events, bookings by day
        $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));

        $usersByDay = User::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')->pluck('count', 'day');

        $eventsByDay = Event::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')->pluck('count', 'day');

        $bookingsByDay = Booking::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')->pluck('count', 'day');

        $revByDay = Booking::join('events', 'bookings.event_id', '=', 'events.id')
            ->selectRaw('DATE(bookings.created_at) as day, SUM(events.price) as total')
            ->where('bookings.created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')->pluck('total', 'day');

        $chart = $days->map(fn($d) => [
            'date'     => $d,
            'users'    => $usersByDay[$d] ?? 0,
            'events'   => $eventsByDay[$d] ?? 0,
            'bookings' => $bookingsByDay[$d] ?? 0,
            'revenue'  => round($revByDay[$d] ?? 0, 2),
        ])->values();

        // Events by status
        $published = Event::count();
        $eventsByStatus = [
            ['label' => 'Published', 'value' => $published, 'color' => '#22c55e'],
            ['label' => 'Pending',   'value' => 0,          'color' => '#f59e0b'],
            ['label' => 'Cancelled', 'value' => 0,          'color' => '#ef4444'],
            ['label' => 'Draft',     'value' => 0,          'color' => '#94a3b8'],
        ];

        // Bookings by status
        $confirmed  = Booking::where('status', 'confirmed')->count();
        $pending    = Booking::where('status', 'pending')->count();
        $cancelled  = Booking::where('status', 'cancelled')->count();
        $bookingsByStatus = [
            ['label' => 'Confirmed', 'value' => $confirmed,  'color' => '#6366f1'],
            ['label' => 'Pending',   'value' => $pending,    'color' => '#f59e0b'],
            ['label' => 'Cancelled', 'value' => $cancelled,  'color' => '#ef4444'],
        ];

        return response()->json([
            'success' => true,
            'chart'   => $chart,
            'events_by_status'   => $eventsByStatus,
            'bookings_by_status' => $bookingsByStatus,
        ]);
    }

    public function recentEvents(Request $request)
    {
        $this->checkAdmin($request);
        $events = Event::with('user')->latest()->limit(5)->get()->map(fn($e) => [
            'id'        => $e->id,
            'title'     => $e->title,
            'organizer' => $e->user?->name ?? '—',
            'date'      => $e->date?->format('d M Y'),
            'status'    => 'Published',
            'bookings'  => $e->bookings()->count(),
            'image'     => $e->image ? asset('storage/' . ltrim($e->image, '/storage/')) : null,
            'category'  => $e->category,
        ]);
        return response()->json(['success' => true, 'events' => $events]);
    }

    public function recentUsers(Request $request)
    {
        $this->checkAdmin($request);
        $users = User::latest()->limit(5)->get()->map(fn($u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'role'       => $u->is_organizer ? 'Organizer' : 'Attendee',
            'joined'     => $u->created_at->diffForHumans(),
            'status'     => 'Active',
            'avatar'     => $u->profile_picture,
        ]);
        return response()->json(['success' => true, 'users' => $users]);
    }

    public function recentBookings(Request $request)
    {
        $this->checkAdmin($request);
        $bookings = Booking::with(['user', 'event'])->latest()->limit(5)->get()->map(fn($b) => [
            'id'         => $b->id,
            'user'       => $b->user?->name ?? '—',
            'event'      => $b->event?->title ?? '—',
            'date'       => $b->created_at->format('d M Y'),
            'amount'     => '$' . number_format($b->event?->price ?? 0, 2),
            'status'     => ucfirst($b->status),
        ]);
        return response()->json(['success' => true, 'bookings' => $bookings]);
    }

    public function recentReviews(Request $request)
    {
        $this->checkAdmin($request);
        $reviews = Review::with(['user', 'event'])->latest()->limit(5)->get()->map(fn($r) => [
            'id'      => $r->id,
            'user'    => $r->user?->name ?? '—',
            'event'   => $r->event?->title ?? '—',
            'rating'  => $r->rating,
            'comment' => $r->comment,
            'date'    => $r->created_at->format('d M Y'),
            'status'  => 'Approved',
        ]);
        return response()->json(['success' => true, 'reviews' => $reviews]);
    }

    public function recentActivity(Request $request)
    {
        $this->checkAdmin($request);

        $activities = collect();

        // Latest bookings
        Booking::with(['user', 'event'])->latest()->limit(3)->get()->each(function ($b) use (&$activities) {
            $activities->push([
                'type'    => 'booking',
                'message' => "Booking confirmed for \"{$b->event?->title}\" by {$b->user?->name}",
                'time'    => $b->created_at->diffForHumans(),
                'color'   => '#22c55e',
            ]);
        });

        // Latest events
        Event::with('user')->latest()->limit(2)->get()->each(function ($e) use (&$activities) {
            $activities->push([
                'type'    => 'event',
                'message' => "New event \"{$e->title}\" created by {$e->user?->name}",
                'time'    => $e->created_at->diffForHumans(),
                'color'   => '#6366f1',
            ]);
        });

        // Latest reviews
        Review::with(['user', 'event'])->latest()->limit(2)->get()->each(function ($r) use (&$activities) {
            $activities->push([
                'type'    => 'review',
                'message' => "Review added for \"{$r->event?->title}\" by {$r->user?->name}",
                'time'    => $r->created_at->diffForHumans(),
                'color'   => '#f59e0b',
            ]);
        });

        // Sort by most recent first (approximate, based on 'time' field being relative)
        return response()->json(['success' => true, 'activities' => $activities->take(8)->values()]);
    }

    public function organizerApplications(Request $request)
    {
        $this->checkAdmin($request);
        $apps = OrganizerApplication::with('user')->latest()->get()->map(fn($a) => [
            'id'           => $a->id,
            'name'         => $a->full_name ?? $a->user?->name ?? '—',
            'email'        => $a->user?->email ?? '—',
            'organization' => $a->organization ?? '—',
            'phone'        => $a->phone ?? '—',
            'motivation'   => $a->motivation ?? '',
            'submitted'    => $a->created_at->format('d M Y'),
            'joined'       => $a->created_at->format('d M Y'),
            'status'       => ucfirst($a->status),
            'user_id'      => $a->user_id,
        ]);
        return response()->json(['success' => true, 'applications' => $apps]);
    }

    public function approveApplication(Request $request, $id)
    {
        $this->checkAdmin($request);
        $app = OrganizerApplication::with('user')->findOrFail($id);
        $app->update(['status' => 'approved']);

        // Grant organizer role to the user
        if ($app->user) {
            $app->user->update(['is_organizer' => true]);

            // Notify the applicant
            \App\Models\Notification::create([
                'user_id'  => $app->user_id,
                'type'     => 'management',
                'title'    => 'Organizer Request Approved! 🎉',
                'message'  => "Congratulations {$app->user->name}! Your request to become an organizer has been approved. You can now create and manage events.",
                'is_read'  => false,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Application approved.']);
    }

    public function rejectApplication(Request $request, $id)
    {
        $this->checkAdmin($request);
        $app = OrganizerApplication::with('user')->findOrFail($id);
        $app->update(['status' => 'rejected']);

        // Notify the applicant
        if ($app->user) {
            \App\Models\Notification::create([
                'user_id'  => $app->user_id,
                'type'     => 'management',
                'title'    => 'Organizer Request Update',
                'message'  => "We've reviewed your organizer application and unfortunately it has not been approved at this time. You may re-apply in the future.",
                'is_read'  => false,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Application rejected.']);
    }

    public function allUsers(Request $request)
    {
        $this->checkAdmin($request);
        $users = User::latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function deleteUser(Request $request, $id)
    {
        $this->checkAdmin($request);
        User::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'User deleted.']);
    }
}
