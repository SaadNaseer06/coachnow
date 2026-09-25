<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Services\CoachAvailabilityService;
use App\Services\SessionBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function slots(Request $request, Coach $coach, CoachAvailabilityService $availability): JsonResponse
    {
        abort_unless($coach->status === 'active', 404);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'days' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $from = isset($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()
            : Carbon::today();
        $to = isset($data['to'])
            ? Carbon::parse($data['to'])->startOfDay()
            : $from->copy()->addDays((int) ($data['days'] ?? 14));

        $slots = $availability->openSlotsForCoach($coach, $from, $to);

        return response()->json([
            'coach' => [
                'id' => $coach->id,
                'slug' => $coach->slug,
                'name' => $coach->display_name,
                'rate' => (float) $coach->rate,
            ],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'slots' => $slots,
        ]);
    }

    public function store(Request $request, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can book sessions.'], 403);
        }

        $data = $request->validate([
            'coach' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'string', 'max:10'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'session_type' => ['nullable', 'string', 'max:160'],
        ]);

        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        try {
            $booking = $bookings->createDirectBooking($coach, $user, [
                'date' => Carbon::parse($data['date'])->toDateString(),
                'time' => $data['time'],
                'location_id' => (int) $data['location_id'],
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'session_type' => $data['session_type'] ?? 'Private 1-on-1',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not book that slot.',
            ], 422);
        }

        $coach->forceFill(['last_active_at' => now()])->save();

        $booking->load(['coach', 'location']);

        return response()->json([
            'message' => 'Session booked.',
            'data' => [
                'reference' => $booking->reference,
                'coach' => $booking->coach?->display_name,
                'date' => $booking->session_date?->toDateString(),
                'time' => $booking->session_time,
                'location' => $booking->location?->name,
                'amount' => (float) $booking->amount,
            ],
        ], 201);
    }
}
