<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\User;
use App\Services\AppMailer;
use App\Services\SessionBookingService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class StripePaymentController extends Controller
{
    public function config(StripeService $stripe): JsonResponse
    {
        return response()->json([
            'enabled' => $stripe->enabled(),
            'sample_mode' => $stripe->sampleMode(),
            'publishable_key' => $stripe->enabled() ? $stripe->publishableKey() : null,
            'convenience_fee' => $stripe->convenienceFeeAmount(request()->user()),
            'currency' => config('services.stripe.currency', 'usd'),
        ]);
    }

    public function setupIntent(Request $request, StripeService $stripe): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if (! $stripe->enabled()) {
            return response()->json(['message' => 'Stripe is not configured.'], 503);
        }

        try {
            return response()->json(['data' => $stripe->createSetupIntent($user)]);
        } catch (ApiErrorException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Create a PaymentIntent for a direct Book Now session (client confirms).
     */
    public function createBookingIntent(Request $request, StripeService $stripe, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can book sessions.'], 403);
        }

        if (! $stripe->enabled()) {
            return response()->json(['message' => 'Stripe is not configured.'], 503);
        }

        $data = $request->validate([
            'coach' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'string', 'max:10'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'session_type' => ['nullable', 'string', 'max:160'],
            'group_session_id' => ['nullable', 'integer'],
            'new_group' => ['nullable', 'boolean'],
            // Several 1-on-1 sessions in one checkout (multi-date / weekly series).
            'slots' => ['nullable', 'array', 'min:1', 'max:'.(int) config('coachnow.booking.max_sessions_per_checkout', 24)],
            'slots.*.date' => ['required_with:slots', 'date', 'after_or_equal:today'],
            'slots.*.time' => ['required_with:slots', 'date_format:H:i'],
            'slots.*.location_id' => ['required_with:slots', 'integer', 'exists:locations,id'],
            'slots.*.duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'recurring' => ['nullable', 'boolean'],
            'players' => ['nullable', 'array', 'min:1', 'max:30'],
            'players.*' => ['required', 'string', 'max:120'],
        ]);

        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        if (! $stripe->canChargeFor($coach)) {
            return response()->json([
                'message' => 'This coach has not finished payout setup yet, so paid bookings are unavailable.',
            ], 422);
        }

        // One convenience fee per checkout, however many players are booked.
        $groupMeta = [];
        if (! empty($data['group_session_id'])) {
            $group = CoachGroupSession::query()->with('coach')->find((int) $data['group_session_id']);
            if (! $group) {
                return response()->json(['message' => 'That group session no longer exists.'], 422);
            }

            $players = $this->playerNames($data, $user);
            try {
                $bookings->assertGroupJoinable($coach, $group, $user, $players);
            } catch (ValidationException $e) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }

            $quote = $bookings->groupCheckoutQuote($group, count($players), $user);
            $sessionCents = (int) round($quote['session_amount'] * 100);
            $convenienceCents = (int) round($quote['convenience_fee'] * 100);
            $groupMeta = [
                'group_session_id' => (string) $group->id,
                'player_count' => (string) count($players),
            ];
        } elseif (! empty($data['slots'])) {
            try {
                $bookings->assertSlotsBookable($coach, $data['slots']);
            } catch (ValidationException $e) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }

            $quote = $bookings->multiSessionQuote($coach, count($data['slots']), $user);
            $sessionCents = (int) round($quote['session_amount'] * 100);
            $convenienceCents = (int) round($quote['convenience_fee'] * 100);
            $groupMeta = [
                'slot_count' => (string) count($data['slots']),
                'slots_hash' => self::slotsHash($data['slots']),
                'recurring' => ! empty($data['recurring']) ? '1' : '0',
            ];
        } elseif (! empty($data['new_group'])) {
            $players = $this->playerNames($data, $user);
            try {
                $bookings->assertNewGroupBookable($coach, $data, $players);
            } catch (ValidationException $e) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }

            $quote = $bookings->newGroupCheckoutQuote($coach, count($players), $user);
            $sessionCents = (int) round($quote['session_amount'] * 100);
            $convenienceCents = (int) round($quote['convenience_fee'] * 100);
            $groupMeta = [
                'new_group' => '1',
                'player_count' => (string) count($players),
            ];
        } else {
            $sessionCents = (int) round(((float) ($coach->rate ?: 0)) * 100);
            $convenienceCents = $stripe->convenienceFeeCents($user);
        }

        if ($sessionCents < 50) {
            return response()->json(['message' => 'This session has no price set for paid checkout.'], 422);
        }

        $amountCents = $sessionCents + $convenienceCents;

        try {
            $intent = $stripe->createDestinationPaymentIntent(
                $user,
                $coach,
                $amountCents,
                [
                    'purpose' => 'booking',
                    'session_amount_cents' => (string) $sessionCents,
                    'convenience_fee_cents' => (string) $convenienceCents,
                    'session_date' => Carbon::parse($data['date'])->toDateString(),
                    'session_time' => $data['time'],
                    'location_id' => (string) $data['location_id'],
                    'duration_minutes' => (string) ($data['duration_minutes'] ?? 60),
                    'session_type' => $coach->resolveSessionType($data['session_type'] ?? null),
                ] + $groupMeta
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage() ?: 'Could not start payment.'], 422);
        }

        return response()->json([
            'data' => [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $amountCents / 100,
                'session_amount' => $sessionCents / 100,
                'convenience_fee' => $convenienceCents / 100,
                'platform_fee' => ((int) ($intent->application_fee_amount ?? 0)) / 100,
                'currency' => $intent->currency,
            ],
        ]);
    }

    /**
     * After client payment succeeds, create the booking.
     */
    public function confirmBooking(Request $request, StripeService $stripe, SessionBookingService $bookings): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can book sessions.'], 403);
        }

        $data = $request->validate([
            'payment_intent_id' => ['required', 'string'],
            'coach' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'string', 'max:10'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'session_type' => ['nullable', 'string', 'max:160'],
            'group_session_id' => ['nullable', 'integer'],
            'new_group' => ['nullable', 'boolean'],
            // Several 1-on-1 sessions in one checkout (multi-date / weekly series).
            'slots' => ['nullable', 'array', 'min:1', 'max:'.(int) config('coachnow.booking.max_sessions_per_checkout', 24)],
            'slots.*.date' => ['required_with:slots', 'date', 'after_or_equal:today'],
            'slots.*.time' => ['required_with:slots', 'date_format:H:i'],
            'slots.*.location_id' => ['required_with:slots', 'integer', 'exists:locations,id'],
            'slots.*.duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'recurring' => ['nullable', 'boolean'],
            'players' => ['nullable', 'array', 'min:1', 'max:30'],
            'players.*' => ['required', 'string', 'max:120'],
        ]);

        try {
            $intent = $stripe->retrievePaymentIntent($data['payment_intent_id']);
        } catch (ApiErrorException $e) {
            return response()->json(['message' => 'Payment could not be verified.'], 422);
        }

        if ($intent->status !== 'succeeded') {
            return response()->json(['message' => 'Payment has not completed yet.'], 422);
        }

        if ((string) ($intent->metadata['user_id'] ?? '') !== (string) $user->id) {
            return response()->json(['message' => 'Payment does not belong to this account.'], 403);
        }

        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || (string) $coach->id !== (string) ($intent->metadata['coach_id'] ?? '')) {
            return response()->json(['message' => 'Payment coach mismatch.'], 422);
        }

        // Same PaymentIntent confirmed twice (double click / retry): return the existing booking.
        $existing = Booking::query()
            ->where('stripe_payment_intent_id', $intent->id)
            ->orderBy('id')
            ->get();
        if ($existing->isNotEmpty()) {
            return $this->bookingConfirmedResponse($existing->first(), $coach, $existing->count(), ((int) $intent->amount) / 100);
        }

        // Several sessions in one checkout: the request must carry exactly the paid-for slots.
        $slotsHash = (string) ($intent->metadata['slots_hash'] ?? '');
        if ($slotsHash !== '') {
            if (empty($data['slots']) || ! hash_equals($slotsHash, self::slotsHash($data['slots']))) {
                return response()->json(['message' => 'Payment does not match these sessions.'], 422);
            }

            try {
                $created = $bookings->createMultipleDirectBookings(
                    $coach,
                    $user,
                    $data['slots'],
                    (string) ($intent->metadata['recurring'] ?? '0') === '1',
                    $intent->id,
                    (string) ($intent->metadata['session_type'] ?? '')
                );
            } catch (ValidationException $e) {
                // A slot was taken between paying and confirming: refund in full rather than book part of it.
                $reason = collect($e->errors())->flatten()->first() ?: 'One of those times is no longer available.';
                try {
                    $stripe->refundPaymentIntent($intent->id, (int) $intent->amount, ['reason' => 'slot_unavailable']);
                    $reason .= ' Your payment has been refunded in full.';
                } catch (\Throwable $refundError) {
                    report($refundError);
                    $reason .= ' Contact support and we’ll refund your payment.';
                }

                return response()->json(['message' => $reason], 422);
            }

            app(AppMailer::class)->notifyBookingCreated($created, 'single', $user);

            return response()->json(
                BookingController::multiBookingPayload($created, $coach, ((int) $intent->amount) / 100),
                201
            );
        }

        $groupId = (string) ($intent->metadata['group_session_id'] ?? '');
        if ($groupId !== '') {
            $players = $this->playerNames($data, $user);
            if ((string) ($data['group_session_id'] ?? '') !== $groupId
                || (string) count($players) !== (string) ($intent->metadata['player_count'] ?? '')) {
                return response()->json(['message' => 'Payment does not match this group booking.'], 422);
            }

            try {
                $created = $bookings->joinGroupSession($coach, $user, (int) $groupId, $players, $intent->id);
            } catch (ValidationException $e) {
                $reason = collect($e->errors())->flatten()->first() ?: 'Could not join that group.';
                report(new \RuntimeException("Paid group join failed after payment {$intent->id}: {$reason}"));

                return response()->json([
                    'message' => $reason.' Your payment was received — contact support and we’ll refund it.',
                ], 422);
            }

            app(AppMailer::class)->notifyBookingCreated($created, 'group_join', $user);

            return $this->bookingConfirmedResponse($created[0], $coach, count($created), ((int) $intent->amount) / 100);
        }

        if ((string) ($intent->metadata['new_group'] ?? '') === '1') {
            $players = $this->playerNames($data, $user);
            if ((string) count($players) !== (string) ($intent->metadata['player_count'] ?? '')
                || Carbon::parse($data['date'])->toDateString() !== (string) ($intent->metadata['session_date'] ?? '')
                || $data['time'] !== (string) ($intent->metadata['session_time'] ?? '')) {
                return response()->json(['message' => 'Payment does not match this group booking.'], 422);
            }

            try {
                $created = $bookings->createPrivateGroupBooking($coach, $user, [
                    'date' => Carbon::parse($data['date'])->toDateString(),
                    'time' => $data['time'],
                    'location_id' => (int) $data['location_id'],
                    'duration_minutes' => $data['duration_minutes'] ?? 60,
                ], $players, $intent->id);
            } catch (ValidationException $e) {
                $reason = collect($e->errors())->flatten()->first() ?: 'Could not book that group.';
                report(new \RuntimeException("Paid private group booking failed after payment {$intent->id}: {$reason}"));

                return response()->json([
                    'message' => $reason.' Your payment was received — contact support and we’ll refund it.',
                ], 422);
            }

            app(AppMailer::class)->notifyBookingCreated($created, 'private_group', $user);

            return $this->bookingConfirmedResponse($created[0], $coach, count($created), ((int) $intent->amount) / 100);
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

        $stripe->applyPaymentToBooking($booking, $intent);
        app(AppMailer::class)->notifyBookingCreated([$booking], 'single', $user);

        return $this->bookingConfirmedResponse($booking, $coach, 1, (float) $booking->amount);
    }

    private function bookingConfirmedResponse(Booking $booking, Coach $coach, int $players, float $amount): JsonResponse
    {
        $booking->loadMissing('location');

        return response()->json([
            'message' => 'Booking confirmed.',
            'data' => [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'coach' => $coach->display_name,
                'date' => $booking->session_date?->toDateString(),
                'time' => substr((string) $booking->session_time, 0, 5),
                'location' => $booking->location?->name,
                'amount' => $amount,
                'players' => $players,
                'payment_status' => $booking->payment_status,
            ],
        ], 201);
    }

    /**
     * Order-independent fingerprint of the sessions being paid for.
     *
     * @param  list<array<string, mixed>>  $slots
     */
    private static function slotsHash(array $slots): string
    {
        $keys = array_map(
            fn ($slot) => Carbon::parse($slot['date'])->toDateString().'|'.substr((string) $slot['time'], 0, 5).'|'.(int) $slot['location_id'],
            $slots
        );
        sort($keys);

        return sha1(implode(',', $keys));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function playerNames(array $data, User $user): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $data['players'] ?? []
        ))));

        return $names !== [] ? $names : [$user->name];
    }
}
