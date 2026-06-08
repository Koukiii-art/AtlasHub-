<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class SavedEventController extends Controller
{
    /** GET /saved-events — list user's saved events */
    public function index(Request $request)
    {
        $saved = $request->user()
            ->savedEvents()
            ->with(['user'])
            ->latest('saved_events.created_at')
            ->get();

        return response()->json(['data' => $saved]);
    }

    /** POST /events/{id}/save — toggle save */
    public function toggle(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $user  = $request->user();

        $exists = $user->savedEvents()->where('event_id', $id)->exists();

        if ($exists) {
            $user->savedEvents()->detach($id);
            return response()->json(['saved' => false, 'message' => 'Event unsaved.']);
        }

        $user->savedEvents()->attach($id);
        return response()->json(['saved' => true, 'message' => 'Event saved.']);
    }

    /** GET /events/{id}/saved-status */
    public function status(Request $request, $id)
    {
        $saved = $request->user()->savedEvents()->where('event_id', $id)->exists();
        return response()->json(['saved' => $saved]);
    }
}
