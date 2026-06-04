<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrganizerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrganizerApplicationController extends Controller
{
    /**
     * Get the current user's organizer application status.
     */
    public function status(Request $request)
    {
        $application = OrganizerApplication::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if (!$application) {
            return response()->json(['status' => 'none', 'application' => null]);
        }

        return response()->json([
            'status' => $application->status,
            'application' => $application,
        ]);
    }

    /**
     * Submit a new organizer application.
     */
    public function store(Request $request)
    {
        // Check if user already has a pending or approved application
        $existing = OrganizerApplication::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You already have an active application.',
                'status' => $existing->status,
                'application' => $existing,
            ], 409);
        }

        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'phone'        => 'required|string|max:30',
            'cin'          => 'nullable|string|max:50',
            'cin_image'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'social_link'  => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'motivation'   => 'required|string|min:20',
        ]);

        $cinImagePath = null;
        if ($request->hasFile('cin_image')) {
            $cinImagePath = $request->file('cin_image')->store('organizer_cin', 'public');
        }

        $application = OrganizerApplication::create([
            'user_id'      => $request->user()->id,
            'full_name'    => $validated['full_name'],
            'phone'        => $validated['phone'],
            'cin'          => $validated['cin'] ?? null,
            'cin_image'    => $cinImagePath,
            'social_link'  => $validated['social_link'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'motivation'   => $validated['motivation'],
            'status'       => 'pending',
        ]);

        return response()->json([
            'message'     => 'Application submitted successfully.',
            'status'      => 'pending',
            'application' => $application,
        ], 201);
    }

    /**
     * Admin: list all applications.
     */
    public function index(Request $request)
    {
        // Simple admin check – you can replace this with a proper role guard
        $applications = OrganizerApplication::with('user')->latest()->get();
        return response()->json(['applications' => $applications]);
    }

    /**
     * Admin: approve or reject an application.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $application = OrganizerApplication::findOrFail($id);
        $application->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes ?? $application->admin_notes,
        ]);

        // If approved, mark user as organizer
        if ($request->status === 'approved') {
            $application->user->update(['is_organizer' => true]);
        }

        return response()->json([
            'message'     => 'Application status updated.',
            'application' => $application,
        ]);
    }
}
