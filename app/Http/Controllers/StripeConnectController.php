<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StripeConnectController extends Controller
{
    public function status(Request $request, StripeService $stripe): JsonResponse
    {
        $coach = $this->currentCoach($request);
        if ($stripe->enabled() && $coach->stripe_account_id) {
            $force = $request->boolean('refresh');
            $cacheKey = 'coach.'.$coach->id.'.stripe_sync';
            if ($force || ! Cache::has($cacheKey)) {
                $coach = $stripe->syncConnectAccount($coach);
                Cache::put($cacheKey, true, now()->addMinutes(2));
            } else {
                $coach = $coach->fresh();
            }
        }

        return response()->json([
            'enabled' => $stripe->enabled(),
            'ready' => $coach->canReceivePayouts(),
            'account_id' => $coach->stripe_account_id,
            'charges_enabled' => (bool) $coach->stripe_charges_enabled,
            'payouts_enabled' => (bool) $coach->stripe_payouts_enabled,
            'details_submitted' => (bool) $coach->stripe_details_submitted,
            'onboarded_at' => $coach->stripe_onboarded_at?->toIso8601String(),
        ]);
    }

    public function onboard(Request $request, StripeService $stripe): RedirectResponse|JsonResponse
    {
        $coach = $this->currentCoach($request);

        if (! $stripe->enabled()) {
            $message = 'Stripe is not configured yet. Add STRIPE_KEY and STRIPE_SECRET to enable payouts.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 503);
            }

            return back()->with('error', $message);
        }

        try {
            $url = $stripe->createAccountOnboardingLink($coach);
        } catch (\Throwable $e) {
            report($e);
            $message = 'Could not start payout setup. Please try again.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return redirect()->away($url);
    }

    public function dashboard(Request $request, StripeService $stripe): RedirectResponse
    {
        $coach = $this->currentCoach($request);
        if (! $stripe->enabled()) {
            return back()->with('error', 'Stripe is not configured.');
        }

        $url = $stripe->createAccountLoginLink($coach);
        if (! $url) {
            return redirect()->route('coach.stripe.onboard');
        }

        return redirect()->away($url);
    }

    public function returnFromStripe(Request $request, StripeService $stripe): RedirectResponse
    {
        $coach = $this->currentCoach($request);
        if ($stripe->enabled()) {
            $stripe->syncConnectAccount($coach);
            Cache::forget('coach.'.$coach->id.'.stripe_sync');
        }

        return redirect()
            ->route('coach.profile')
            ->with('success', $coach->fresh()->canReceivePayouts()
                ? 'Payout setup complete. You can receive paid bookings.'
                : 'Stripe onboarding saved. Finish any remaining steps if payouts are still pending.');
    }

    public function refresh(Request $request): RedirectResponse
    {
        return redirect()
            ->route('coach.stripe.onboard')
            ->with('error', 'Please continue payout setup to finish connecting your bank account.');
    }

    private function currentCoach(Request $request): Coach
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCoach(), 403);

        $coach = Coach::query()->where('user_id', $user->id)->first();
        abort_unless($coach, 403);

        return $coach;
    }
}
