<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

/**
 * Parent "Family membership" ($10/mo): no $2 booking fees, session requests, coach reports.
 */
class SubscriptionController extends Controller
{
    public function checkout(Request $request, StripeService $stripe): RedirectResponse
    {
        $user = $this->parent($request);

        if ($user->hasActiveSubscription()) {
            return redirect()->route('player-dashboard')->with('success', 'Your Family membership is already active.');
        }

        // Sample mode: no live billing — activate a one-month membership so flows can be tested.
        if (! $stripe->enabled()) {
            $user->forceFill([
                'subscription_status' => 'active',
                'subscription_ends_at' => now()->addMonth(),
                'subscription_cancel_at_period_end' => false,
            ])->save();

            return redirect()->route('player-dashboard')->with('success', 'Family membership activated (sample mode — no charge).');
        }

        try {
            $session = $stripe->createSubscriptionCheckout(
                $user,
                route('subscription.return', absolute: true).'?session_id={CHECKOUT_SESSION_ID}',
                route('player-dashboard', absolute: true).'#membership'
            );
        } catch (ApiErrorException $e) {
            report($e);

            return redirect()->route('player-dashboard')->with('error', 'Could not start checkout: '.$e->getMessage());
        }

        return redirect()->away($session->url);
    }

    public function returnFromCheckout(Request $request, StripeService $stripe): RedirectResponse
    {
        $user = $this->parent($request);
        $sessionId = (string) $request->query('session_id', '');

        if ($stripe->enabled() && $sessionId !== '') {
            try {
                $stripe->syncSubscriptionFromCheckout($user, $sessionId);
            } catch (ApiErrorException $e) {
                report($e);
            }
        }

        return redirect()->route('player-dashboard')->with(
            'success',
            $user->fresh()->hasActiveSubscription()
                ? 'Welcome to the Family membership — no more booking fees.'
                : 'Payment received. Your membership will show as active in a moment.'
        );
    }

    /**
     * Cancel at the end of the paid period (benefits stay until then), or undo that.
     */
    public function update(Request $request, StripeService $stripe): RedirectResponse
    {
        $user = $this->parent($request);
        $cancel = $request->boolean('cancel');

        if ($stripe->enabled() && filled($user->stripe_subscription_id)) {
            try {
                $stripe->setSubscriptionCancelAtPeriodEnd($user, $cancel);
            } catch (ApiErrorException $e) {
                report($e);

                return back()->with('error', 'Could not update your membership: '.$e->getMessage());
            }
        } else {
            $user->forceFill(['subscription_cancel_at_period_end' => $cancel])->save();
        }

        $endsAt = $user->fresh()->subscription_ends_at?->format('M j, Y');

        return back()->with('success', $cancel
            ? 'Membership cancelled. Your benefits continue until '.($endsAt ?? 'the end of this period').'.'
            : 'Membership renewed — it will continue automatically.');
    }

    private function parent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAthlete(), 403);

        return $user;
    }
}
