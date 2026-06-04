<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    /** GET /settings */
    public function show(Request $request)
    {
        $user = $request->user();
        $defaults = [
            'language'                    => 'English',
            'ticket_purchase_confirmation' => true,
            'event_updates'               => true,
            'event_reminders'             => true,
            'event_cancellation_alerts'   => true,
            'show_events_near_location'   => true,
            'recommend_trending_events'   => true,
        ];

        $settings = array_merge($defaults, $user->settings ?? []);
        return response()->json(['settings' => $settings]);
    }

    /** PUT /settings */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'language'                    => 'nullable|string|max:50',
            'ticket_purchase_confirmation' => 'nullable|boolean',
            'event_updates'               => 'nullable|boolean',
            'event_reminders'             => 'nullable|boolean',
            'event_cancellation_alerts'   => 'nullable|boolean',
            'show_events_near_location'   => 'nullable|boolean',
            'recommend_trending_events'   => 'nullable|boolean',
        ]);

        $current = $user->settings ?? [];
        $merged  = array_merge($current, $validated);

        $user->update(['settings' => $merged]);

        return response()->json(['settings' => $merged, 'message' => 'Settings saved.']);
    }
}
