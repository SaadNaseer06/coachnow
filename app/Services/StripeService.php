<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripeService
{
    private ?StripeClient $client = null;

    public function enabled(): bool
    {
        if (config('coachnow.payments.sample_mode')) {
            return false;
        }

        return filled(config('services.stripe.secret'))
            && filled(config('services.stripe.key'));
    }

    public function sampleMode(): bool
    {
        return (bool) config('coachnow.payments.sample_mode');
    }

    /**
     * True when the configured secret key is a Stripe test key (sk_test_ / rk_test_).
     */
    public function testMode(): bool
    {
        $secret = (string) config('services.stripe.secret');

        return str_starts_with($secret, 'sk_test_') || str_starts_with($secret, 'rk_test_');
    }

    public function client(): StripeClient
    {
        if (! $this->enabled()) {
            throw new \RuntimeException('Stripe is not configured.');
        }

        return $this->client ??= new StripeClient((string) config('services.stripe.secret'));
    }

    public function publishableKey(): ?string
    {
        return config('services.stripe.key');
    }

    public function ensureCustomer(User $user): string
    {
        if (filled($user->stripe_customer_id)) {
            // Customer IDs saved under other keys (live vs test, or another account)
            // fail with "No such customer" — recreate instead of blocking checkout.
            try {
                $existing = $this->client()->customers->retrieve((string) $user->stripe_customer_id);
                if (! ($existing->deleted ?? false)) {
                    return (string) $user->stripe_customer_id;
                }
            } catch (InvalidRequestException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
            }

            Log::info('Recreating missing Stripe customer', ['user_id' => $user->id]);
        }

        $customer = $this->client()->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
                'role' => (string) $user->role,
            ],
        ]);

        $user->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    /**
     * Charge the CoachNow booking fee. It goes to CoachNow; the session itself is paid to the coach.
     *
     * @param  array<string, string>  $metadata
     */
    public function createFeePaymentIntent(User $payer, int $amountCents, string $description, array $metadata = []): \Stripe\PaymentIntent
    {
        return $this->client()->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => config('services.stripe.currency', 'usd'),
            'customer' => $this->ensureCustomer($payer),
            'description' => $description,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            // Cards, Apple Pay and Google Pay only: no pay-later on a $2 fee, and no bank debits,
            // which take days to clear and would hold up the booking. Link is hidden in the form.
            'excluded_payment_method_types' => ['klarna', 'affirm', 'afterpay_clearpay', 'us_bank_account', 'cashapp', 'amazon_pay', 'crypto'],
        ]);
    }

    /**
     * Lets Stripe's card form show the parent's saved cards and offer to save a new one.
     * Card details stay with Stripe.
     */
    public function createCardSession(User $user): string
    {
        $session = $this->client()->customerSessions->create([
            'customer' => $this->ensureCustomer($user),
            'components' => [
                'payment_element' => [
                    'enabled' => true,
                    'features' => [
                        'payment_method_redisplay' => 'enabled',
                        'payment_method_save' => 'enabled',
                        'payment_method_save_usage' => 'on_session',
                        'payment_method_remove' => 'enabled',
                    ],
                ],
            ],
        ]);

        return (string) $session->client_secret;
    }

    /**
     * Full or partial refund. The same idempotency key never refunds twice.
     *
     * @param  array<string, string>  $metadata
     */
    public function refundPaymentIntent(string $paymentIntentId, int $amountCents, array $metadata = [], ?string $idempotencyKey = null): ?\Stripe\Refund
    {
        if ($amountCents <= 0) {
            return null;
        }

        return $this->client()->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
            'metadata' => $metadata,
        ], $idempotencyKey ? ['idempotency_key' => $idempotencyKey] : []);
    }

    /**
     * Hosted Stripe Checkout for the parent Family membership (monthly).
     */
    public function createSubscriptionCheckout(User $user, string $successUrl, string $cancelUrl): \Stripe\Checkout\Session
    {
        return $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $this->ensureCustomer($user),
            'client_reference_id' => (string) $user->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'usd'),
                    'unit_amount' => (int) round(((float) config('coachnow.subscription.price', 10)) * 100),
                    'recurring' => ['interval' => 'month'],
                    'product_data' => ['name' => (string) config('coachnow.subscription.name', 'CoachNow Family Membership')],
                ],
            ]],
            'subscription_data' => ['metadata' => ['user_id' => (string) $user->id, 'purpose' => 'parent_membership']],
            'metadata' => ['user_id' => (string) $user->id, 'purpose' => 'parent_membership'],
            'custom_text' => ['submit' => ['message' => self::membershipCheckoutTerms()]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    public static function membershipCheckoutTerms(): string
    {
        $price = number_format((float) config('coachnow.subscription.price', 10), 0);

        return "You'll be charged \${$price} today, then \${$price} every month on your billing date. "
            .'Your membership renews automatically until you cancel. Cancel anytime from your dashboard: '
            .'future charges stop and your access continues until the end of the period you paid for.';
    }

    public function syncSubscriptionFromCheckout(User $user, string $checkoutSessionId): void
    {
        $session = $this->client()->checkout->sessions->retrieve($checkoutSessionId);
        if ((string) ($session->client_reference_id ?? '') !== (string) $user->id || ! $session->subscription) {
            return;
        }

        $this->applySubscription($this->client()->subscriptions->retrieve((string) $session->subscription));
    }

    public function setSubscriptionCancelAtPeriodEnd(User $user, bool $cancel): void
    {
        $subscription = $this->client()->subscriptions->update((string) $user->stripe_subscription_id, [
            'cancel_at_period_end' => $cancel,
        ]);
        $this->applySubscription($subscription);
    }

    /**
     * Copy a Stripe subscription's state onto its user (checkout return + webhooks).
     */
    public function applySubscription(\Stripe\Subscription $subscription): ?User
    {
        $userId = $subscription->metadata['user_id'] ?? null;
        $user = $userId
            ? User::query()->find($userId)
            : User::query()->where('stripe_subscription_id', $subscription->id)
                ->orWhere('stripe_customer_id', (string) $subscription->customer)
                ->first();

        if (! $user) {
            return null;
        }

        // current_period_end moved onto subscription items in newer API versions.
        $periodEnd = $subscription->current_period_end
            ?? ($subscription->items->data[0]->current_period_end ?? null);

        $user->forceFill([
            'stripe_subscription_id' => $subscription->id,
            'subscription_status' => (string) $subscription->status,
            'subscription_ends_at' => $periodEnd ? \Illuminate\Support\Carbon::createFromTimestamp((int) $periodEnd) : null,
            'subscription_cancel_at_period_end' => (bool) ($subscription->cancel_at_period_end ?? false),
        ])->save();

        $this->confirmMembershipOnce($user, (string) $subscription->id);

        return $user;
    }

    /**
     * Checkout's return page and Stripe's webhook both land here, so only whichever
     * claims the subscription first sends the email.
     */
    private function confirmMembershipOnce(User $user, string $subscriptionId): void
    {
        if (! $user->hasActiveSubscription()) {
            return;
        }

        $claimed = User::query()
            ->whereKey($user->id)
            ->where(fn ($q) => $q->whereNull('membership_email_sent_for')->orWhere('membership_email_sent_for', '!=', $subscriptionId))
            ->update(['membership_email_sent_for' => $subscriptionId]);

        if ($claimed) {
            app(AppMailer::class)->sendMembershipConfirmed($user);
        }
    }

    public function retrievePaymentIntent(string $id): \Stripe\PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($id);
    }

    public function paymentMethodLabel(?string $paymentMethodId): ?string
    {
        if (! $paymentMethodId) {
            return null;
        }

        try {
            $pm = $this->client()->paymentMethods->retrieve($paymentMethodId);
            $brand = ucfirst((string) ($pm->card->brand ?? 'Card'));
            $last4 = (string) ($pm->card->last4 ?? '••••');

            return $brand.' ···· '.$last4;
        } catch (ApiErrorException) {
            return 'Card on file';
        }
    }
}
