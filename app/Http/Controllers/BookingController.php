<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Services\AppMailer;
use App\Services\CoachAvailabilityService;
use App\Services\SessionBookingService;
use App\Services\StripeService;
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
            'days' => ['nullable', 'integer', 'min:1', 'max:62'],
        ]);

        // Month-at-a-time for the Book Now calendar, never past today or the booking horizon.
        $horizon = Carbon::today()->addDays((int) config('coachnow.booking.horizon_days', 180));
        $from = isset($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()->max(Carbon::today())
            : Carbon::today();
        $to = isset($data['to'])
            ? Carbon::parse($data['to'])->startOfDay()
            : $from->copy()->addDays((int) ($data['days'] ?? 14));
        $to = $to->min($from->copy()->addDays(62))->min($horizon);

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
            'horizon' => $horizon->toDateString(),
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
            'new_group' => ['nullable', 'boolean'],
            'players' => ['nullable', 'array', 'min:1', 'max:30'],
            'players.*' => ['required', 'string', 'max:120'],
        ]);

        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        $newGroup = (bool) ($data['new_group'] ?? false);
        $quote = $newGroup
            ? $bookings->newGroupCheckoutQuote($coach, count($data['players'] ?? []), $user)
            : $bookings->multiSessionQuote($coach, 1, $user);

        if ($blocked = $this->feeNotPaid($quote)) {
            return $blocked;
        }

        $slot = [
            'date' => Carbon::parse($data['date'])->toDateString(),
            'time' => $data['time'],
            'location_id' => (int) $data['location_id'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
        ];

        try {
            $created = $newGroup
                ? $bookings->createPrivateGroupBooking($coach, $user, $slot, $data['players'] ?? [])
                : [$bookings->createDirectBooking($coach, $user, $slot + ['session_type' => $coach->resolveSessionType($data['session_type'] ?? null)])];
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not book that slot.',
            ], 422);
        }

        $coach->forceFill(['last_active_at' => now()])->save();
        app(AppMailer::class)->notifyBookingCreated($created, $newGroup ? 'private_group' : 'single', $user);

        return response()->json(self::bookedPayload($created, $coach), 201);
    }

    /**
     * Non-members pay the CoachNow fee on the booking page first. Members, free sessions and
     * sample mode have nothing to pay.
     *
     * @param  array<string, mixed>  $quote
     */
    private function feeNotPaid(array $quote): ?JsonResponse
    {
        $stripe = app(StripeService::class);
        if ($quote['convenience_fee'] <= 0 || $stripe->sampleMode()) {
            return null;
        }

        return $stripe->enabled()
            ? response()->json(['message' => 'Please pay the CoachNow fee on this page to finish booking.', 'requires_stripe' => true], 422)
            : response()->json(['message' => 'Booking checkout isn’t available right now. Please try again shortly.'], 503);
    }

    /**
     * Several 1-on-1 sessions with the same coach at once (picked dates or a weekly series).
     * Non-members pay the fee first (booking-intent with `slots`); this books for members,
     * free sessions and sample mode.
     */
    public function storeMultiple(Request $request, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can book sessions.'], 403);
        }

        $data = $request->validate(self::multiSlotRules());
        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        $quote = $bookings->multiSessionQuote($coach, count($data['slots']), $user);
        if ($blocked = $this->feeNotPaid($quote)) {
            return $blocked;
        }

        try {
            $created = $bookings->createMultipleDirectBookings($coach, $user, $data['slots'], (bool) ($data['recurring'] ?? false), null, $data['session_type'] ?? null);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first() ?: 'Could not book those sessions.'], 422);
        }

        $coach->forceFill(['last_active_at' => now()])->save();
        app(AppMailer::class)->notifyBookingCreated($created, 'single', $user);

        return response()->json(self::bookedPayload($created, $coach), 201);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function multiSlotRules(): array
    {
        $max = (int) config('coachnow.booking.max_sessions_per_checkout', 24);

        return [
            'coach' => ['required', 'string', 'max:120'],
            'slots' => ['required', 'array', 'min:1', "max:{$max}"],
            'slots.*.date' => ['required', 'date', 'after_or_equal:today'],
            'slots.*.time' => ['required', 'date_format:H:i'],
            'slots.*.location_id' => ['required', 'integer', 'exists:locations,id'],
            'slots.*.duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'recurring' => ['nullable', 'boolean'],
            'session_type' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * What the parent sees after booking: what's booked, what they owe the coach and how to pay.
     *
     * @param  list<Booking>  $created
     * @return array<string, mixed>
     */
    public static function bookedPayload(array $created, Coach $coach): array
    {
        $first = $created[0];
        $first->loadMissing('location');
        $coach->loadMissing('user');
        $isGroup = (bool) $first->group_session_id;
        $sessions = count(array_unique(array_map(fn (Booking $b) => $b->session_date?->toDateString().' '.$b->session_time, $created)));
        $players = $isGroup ? count($created) : 1;
        $contact = $coach->contactForFamilies();

        return [
            'message' => match (true) {
                $isGroup && $first->groupSession?->is_public => 'You’re in — '.$players.' player'.($players === 1 ? '' : 's').' booked on this group session.',
                $isGroup => 'Private group booked.',
                $sessions > 1 => "{$sessions} sessions booked.",
                default => 'Session booked.',
            },
            'data' => [
                'reference' => $first->reference,
                'references' => array_map(fn (Booking $b) => $b->reference, $created),
                'coach' => $coach->display_name,
                'date' => $first->session_date?->toDateString(),
                'time' => substr((string) $first->session_time, 0, 5),
                'location' => $first->location?->name,
                'group_session_id' => $first->group_session_id,
                'players' => $players,
                'sessions' => $sessions,
                'series' => (bool) $first->series_id,
                'dates' => array_map(fn (Booking $b) => $b->whenLabel(), $created),
                'due_to_coach' => round(array_sum(array_map(fn (Booking $b) => (float) $b->amount, $created)), 2),
                'convenience_fee' => round(array_sum(array_map(fn (Booking $b) => (float) $b->platform_fee_amount, $created)), 2),
                'payment_status' => $first->payment_status,
                'how_to_pay' => $contact['payment_note'],
                'coach_email' => $contact['email'],
                'coach_phone' => $contact['phone'],
            ],
        ];
    }

    public function cancel(Request $request, string $reference, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        $booking = Booking::query()->where('reference', $reference)->firstOrFail();

        try {
            $bookings->cancelByAthlete($user, $booking);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        app(AppMailer::class)->notifyBookingCancelled($booking->fresh());

        return response()->json([
            'message' => 'Booking cancelled. If you’ve already paid your coach, contact them about a refund.',
            'data' => ['reference' => $booking->reference],
        ]);
    }

    /**
     * Join a public group session for one or more players. Non-members pay the fee first
     * (booking-intent → confirm-booking); this books for members, free groups and sample mode.
     */
    public function requestJoin(Request $request, CoachGroupSession $group, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can join group sessions.'], 403);
        }

        $data = $request->validate([
            'players' => ['nullable', 'array', 'min:1', 'max:10'],
            'players.*' => ['required', 'string', 'max:120'],
        ]);

        $coach = $group->coach;
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        $players = $data['players'] ?? [$user->name];
        $quote = $bookings->groupCheckoutQuote($group, count($players), $user);

        if ($blocked = $this->feeNotPaid($quote)) {
            return $blocked;
        }

        try {
            $created = $bookings->joinGroupSession($coach, $user, (int) $group->id, $players);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not join this group session.',
            ], 422);
        }

        app(AppMailer::class)->notifyBookingCreated($created, 'group_join', $user);

        return response()->json(self::bookedPayload($created, $coach), 201);
    }
}
