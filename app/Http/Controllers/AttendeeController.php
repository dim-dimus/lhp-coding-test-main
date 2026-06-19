<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendeeRequest;
use App\Mail\AttendanceConfirmed;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class AttendeeController extends Controller
{
    public function store(StoreAttendeeRequest $request, Event $event): RedirectResponse|JsonResponse
    {
        $attendee = $event->attendees()->firstOrCreate(
            ['email' => $request->string('email')->toString()],
            [
                'name' => $request->string('name')->toString(),
                'status' => $request->input('status', 'interested'),
            ],
        );

        $created = $attendee->wasRecentlyCreated;

        if ($created) {
            Mail::to($attendee->email)->queue(new AttendanceConfirmed($attendee));
        }

        $message = $created
            ? "You're on the list — a confirmation email is on its way."
            : "You're already on the list for this event.";

        if ($request->wantsJson()) {
            return response()->json(['created' => $created, 'message' => $message]);
        }

        return back()->with($created ? 'success' : 'info', $message);
    }
}
