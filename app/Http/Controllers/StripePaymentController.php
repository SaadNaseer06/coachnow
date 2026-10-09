<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Models\User;
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
            'publishable_key' => $stripe->publishableKey(),
            'deposit_amount' => (float) config('coachnow.payments.deposit_amount', 10),
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
    public function createBookingIntent(Request $request, StripeService $stripe): JsonResponse
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
        ]);

        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        if (! $coach->canReceivePayouts()) {
            return response()->json([
                'message' => 'This coach has not finished payout setup yet, so paid bookings are unavailable.',
            ], 422);
        }

        $amountCents = max(50, (int) round(((float) ($coach->rate ?: 0)) * 100));
        if ($amountCents < 50) {
            return response()->json(['message' => 'Coach rate is not set for paid bookings.'], 422);
        }

        try {
            $intent = $stripe->createDestinationPaymentIntent(
                $user,
                $coach,
                $amountCents,
                [
                    'purpose' => 'booking',
                    'session_date' => Carbon::parse($data['date'])->toDateString(),
                    'session_time' => $data['time'],
                    'location_id' => (string) $data['location_id'],
                    'duration_minutes' => (string) ($data['duration_minutes'] ?? 60),
                    'session_type' => (string) ($data['session_type'] ?? 'Private 1-on-1'),
                ]
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

        $stripe->applyPaymentToBooking($booking, $intent);

        return response()->json([
            'message' => 'Booking confirmed.',
            'data' => [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'coach' => $coach->display_name,
                'date' => $booking->session_date?->toDateString(),
                'time' => substr((string) $booking->session_time, 0, 5),
                'location' => $booking->location?->name,
                'amount' => (float) $booking->amount,
                'payment_status' => $booking->payment_status,
            ],
        ], 201);
    }

    /**
     * Create a deposit PaymentIntent for a session-request join (client confirms).
     */
    public function createDepositIntent(Request $request, StripeService $stripe): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if (! $stripe->enabled()) {
            return response()->json(['message' => 'Stripe is not configured.'], 503);
        }

        $data = $request->validate([
            'coach_id' => ['required', 'integer', 'exists:coaches,id'],
            'purpose' => ['nullable', 'string', 'max:40'],
            'session_request_reference' => ['nullable', 'string', 'max:40'],
        ]);

        $coach = Coach::query()->whereKey($data['coach_id'])->firstOrFail();
        if (! $coach->canReceivePayouts()) {
            return response()->json(['message' => 'This coach cannot receive payouts yet.'], 422);
        }

        try {
            $intent = $stripe->createDestinationPaymentIntent(
                $user,
                $coach,
                $stripe->depositAmountCents(),
                [
                    'purpose' => $data['purpose'] ?? 'deposit',
                    'session_request_reference' => (string) ($data['session_request_reference'] ?? ''),
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => ((int) $intent->amount) / 100,
            ],
        ]);
    }
}
