<?php

namespace App\Http\Controllers;

use App\Models\BookingCheckout;
use App\Models\Coach;
use App\Models\User;
use App\Services\BookingCheckoutService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'convenience_fee' => User::convenienceFeeFor(request()->user()),
            'currency' => config('services.stripe.currency', 'usd'),
        ]);
    }

    /**
     * Start paying the CoachNow fee for a Book Now booking. The session itself is paid to the coach.
     */
    public function createBookingIntent(Request $request, StripeService $stripe, BookingCheckoutService $checkouts): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can book sessions.'], 403);
        }

        if (! $stripe->enabled()) {
            return response()->json(['message' => 'Booking checkout isn’t available right now. Please try again shortly.'], 503);
        }

        $data = $request->validate(BookingCheckoutService::rules());

        $coach = Coach::resolveFromPublicToken($data['coach']);
        if (! $coach || $coach->status !== 'active') {
            return response()->json(['message' => 'That coach is not available.'], 422);
        }

        try {
            return response()->json(['data' => $checkouts->start($user, $coach, $data)]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (ApiErrorException $e) {
            report($e);

            return response()->json(['message' => 'Could not start the payment. Please try again.'], 422);
        }
    }

    /**
     * The fee is paid: book the sessions. Safe to call twice, and the webhook does the same if
     * this call never arrives.
     */
    public function confirmBooking(Request $request, BookingCheckoutService $checkouts): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isAthlete()) {
            return response()->json(['message' => 'Only athletes can book sessions.'], 403);
        }

        $data = $request->validate(['payment_intent_id' => ['required', 'string', 'max:120']]);

        try {
            $checkout = $checkouts->complete($data['payment_intent_id'], $user);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (ApiErrorException $e) {
            report($e);

            return response()->json(['message' => 'Payment could not be verified. If you were charged, your booking will be confirmed shortly.'], 422);
        }

        if ($checkout->status !== BookingCheckout::BOOKED) {
            $fee = '$'.number_format($checkout->fee_cents / 100, 2);

            return response()->json([
                'message' => trim(($checkout->message ?: 'That time is no longer available.').' '.($checkout->status === BookingCheckout::REFUNDED
                    ? "Your {$fee} CoachNow fee has been refunded."
                    : "Your {$fee} CoachNow fee will be refunded.")),
            ], 422);
        }

        return response()->json(BookingController::bookedPayload($checkout->bookings(), $checkout->coach), 201);
    }
}
