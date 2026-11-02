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
        $price = $newGroup
            ? $bookings->newGroupCheckoutQuote($coach, count($data['players'] ?? []), $user)['total']
            : (float) ($coach->rate ?? 0);

        $stripe = app(StripeService::class);
        if (
            $stripe->enabled()
            && config('coachnow.payments.require_payouts_for_paid')
            && $price > 0
        ) {
            if (! $stripe->canChargeFor($coach)) {
                return response()->json([
                    'message' => 'This coach has not finished payout setup yet.',
                ], 422);
            }

            return response()->json([
                'message' => 'Paid bookings must complete Stripe checkout on this page.',
                'requires_stripe' => true,
            ], 422);
        }

        if ($newGroup) {
            try {
                $created = $bookings->createPrivateGroupBooking($coach, $user, [
                    'date' => Carbon::parse($data['date'])->toDateString(),
                    'time' => $data['time'],
                    'location_id' => (int) $data['location_id'],
                    'duration_minutes' => $data['duration_minutes'] ?? 60,
                ], $data['players'] ?? []);
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first() ?: 'Could not book that group.',
                ], 422);
            }

            app(AppMailer::class)->notifyBookingCreated($created, 'private_group', $user);
            $first = $created[0]->load(['coach', 'location']);

            return response()->json([
                'message' => 'Private group booked.',
                'data' => [
                    'reference' => $first->reference,
                    'coach' => $first->coach?->display_name,
                    'date' => $first->session_date?->toDateString(),
                    'time' => $first->session_time,
                    'location' => $first->location?->name,
                    'players' => count($created),
                    'amount' => $price,
                ],
            ], 201);
        }

        try {
            $booking = $bookings->createDirectBooking($coach, $user, [
                'date' => Carbon::parse($data['date'])->toDateString(),
                'time' => $data['time'],
                'location_id' => (int) $data['location_id'],
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'session_type' => $coach->resolveSessionType($data['session_type'] ?? null),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not book that slot.',
            ], 422);
        }

        $coach->forceFill(['last_active_at' => now()])->save();
        app(AppMailer::class)->notifyBookingCreated([$booking], 'single', $user);

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

    /**
     * Several 1-on-1 sessions with the same coach in one checkout (picked dates or a weekly
     * series). Paid sessions go through Stripe (booking-intent with `slots`); this handles
     * sample mode and free sessions.
     */
    public function storeMultiple(Request $request, SessionBookingService $bookings, StripeService $stripe): JsonResponse
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
        if ($stripe->enabled() && $quote['total'] > 0) {
            return response()->json([
                'message' => 'Paid bookings must complete Stripe checkout on this page.',
                'requires_stripe' => true,
            ], 422);
        }

        try {
            $created = $bookings->createMultipleDirectBookings($coach, $user, $data['slots'], (bool) ($data['recurring'] ?? false), null, $data['session_type'] ?? null);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first() ?: 'Could not book those sessions.'], 422);
        }

        $coach->forceFill(['last_active_at' => now()])->save();
        app(AppMailer::class)->notifyBookingCreated($created, 'single', $user);

        return response()->json(self::multiBookingPayload($created, $coach, $quote['total']), 201);
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
     * @param  list<Booking>  $created
     * @return array<string, mixed>
     */
    public static function multiBookingPayload(array $created, Coach $coach, float $total): array
    {
        $count = count($created);

        return [
            'message' => $count === 1 ? 'Session booked.' : "{$count} sessions booked.",
            'data' => [
                'coach' => $coach->display_name,
                'sessions' => count($created),
                'series' => (bool) ($created[0]->series_id ?? null),
                'amount' => $total,
                'dates' => array_map(fn (Booking $b) => $b->whenLabel(), $created),
            ],
        ];
    }

    public function cancel(Request $request, string $reference, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        $booking = Booking::query()->where('reference', $reference)->firstOrFail();

        try {
            $refund = $bookings->cancelByAthlete($user, $booking);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            report($e);

            return response()->json(['message' => 'The refund could not be processed: '.$e->getMessage()], 422);
        }

        app(AppMailer::class)->notifyBookingCancelled($booking->fresh(), $refund);

        return response()->json([
            'message' => $refund > 0
                ? 'Booking cancelled. $'.number_format($refund, 2).' will be refunded to your card.'
                : 'Booking cancelled.',
            'data' => ['reference' => $booking->reference, 'refund' => $refund],
        ]);
    }

    /**
     * Join a public group session for one or more players. Paid groups go through
     * Stripe checkout (booking-intent → confirm-booking); this endpoint handles
     * sample mode and free groups.
     */
    public function requestJoin(Request $request, CoachGroupSession $group, SessionBookingService $bookings, StripeService $stripe): JsonResponse
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

        if ($stripe->enabled() && $quote['total'] > 0) {
            return response()->json([
                'message' => 'Paid group sessions must complete Stripe checkout on this page.',
                'requires_stripe' => true,
            ], 422);
        }

        try {
            $created = $bookings->joinGroupSession($coach, $user, (int) $group->id, $players);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not join this group session.',
            ], 422);
        }

        app(AppMailer::class)->notifyBookingCreated($created, 'group_join', $user);
        $group->refresh();
        $count = count($created);

        return response()->json([
            'message' => 'You’re in — '.$count.' player'.($count === 1 ? '' : 's').' booked on this group session.',
            'data' => [
                'group_session_id' => $group->id,
                'references' => array_map(fn ($b) => $b->reference, $created),
                'players' => $count,
                'amount' => $quote['total'],
                'convenience_fee' => $quote['convenience_fee'],
                'label' => $group->session_type.' · '.$group->capacityLabel(),
            ],
        ], 201);
    }
}
