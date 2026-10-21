<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coach;
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

    /**
     * Whether a card payment can be taken for this coach right now.
     * Live: coach must finish Stripe Connect payouts. Test keys: always allowed —
     * unfinished coaches are charged to the platform account so checkout can be tested.
     */
    public function canChargeFor(Coach $coach): bool
    {
        return $coach->canReceivePayouts() || $this->testMode();
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

    public function depositAmountCents(): int
    {
        return max(50, (int) round(((float) config('coachnow.payments.deposit_amount', 10)) * 100));
    }

    /**
     * What a family pays for a Request a Session deposit: deposit + one convenience fee.
     */
    public function depositChargeCents(): int
    {
        return $this->depositAmountCents() + $this->convenienceFeeCents();
    }

    public function convenienceFeeCents(): int
    {
        return max(0, (int) round(((float) config('coachnow.payments.convenience_fee', 2)) * 100));
    }

    public function convenienceFeeAmount(): float
    {
        return round($this->convenienceFeeCents() / 100, 2);
    }

    /**
     * Platform take per booking transaction (not per player).
     * Launch default: flat $2 convenience fee (+ optional percent/fixed from env).
     */
    public function platformFeeCents(int $amountCents, bool $includeConvenience = true): int
    {
        if ($amountCents <= 0) {
            return 0;
        }

        $percent = (float) config('coachnow.payments.platform_fee_percent', 0);
        $fixed = (int) round(((float) config('coachnow.payments.platform_fee_fixed', 0)) * 100);
        $fee = ($includeConvenience ? $this->convenienceFeeCents() : 0)
            + (int) round($amountCents * ($percent / 100))
            + $fixed;

        // Leave at least $0.50 for the coach when amount is above the fee.
        $maxFee = max(0, $amountCents - 50);

        return max(0, min($maxFee, $fee));
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

    public function createSetupIntent(User $user): array
    {
        $customerId = $this->ensureCustomer($user);
        $intent = $this->client()->setupIntents->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'usage' => 'off_session',
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'setup_intent_id' => $intent->id,
            'customer_id' => $customerId,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function createDestinationPaymentIntent(
        User $payer,
        Coach $coach,
        int $amountCents,
        array $metadata = [],
        ?string $paymentMethodId = null,
        bool $confirm = false,
        bool $offSession = false,
    ): \Stripe\PaymentIntent {
        if (! $this->canChargeFor($coach)) {
            throw new \RuntimeException('This coach has not finished payout setup.');
        }

        $customerId = $this->ensureCustomer($payer);
        // Every checkout (booking, group join, request deposit) carries one convenience fee.
        $fee = $this->platformFeeCents($amountCents);
        $destination = $coach->canReceivePayouts();

        $params = [
            'amount' => $amountCents,
            'currency' => config('services.stripe.currency', 'usd'),
            'customer' => $customerId,
            'metadata' => array_merge([
                'coach_id' => (string) $coach->id,
                'user_id' => (string) $payer->id,
                'platform_fee_cents' => (string) $fee,
                'test_platform_charge' => $destination ? '0' : '1',
            ], $metadata),
        ];

        if ($destination) {
            $params['application_fee_amount'] = $fee;
            $params['transfer_data'] = [
                'destination' => $coach->stripe_account_id,
            ];
        }

        if ($paymentMethodId) {
            $params['payment_method'] = $paymentMethodId;
            $params['confirm'] = $confirm;
            $params['off_session'] = $offSession;
            $params['payment_method_types'] = ['card'];
        } else {
            $params['automatic_payment_methods'] = [
                'enabled' => true,
                'allow_redirects' => 'never',
            ];
        }

        return $this->client()->paymentIntents->create($params);
    }

    public function ensureConnectAccount(Coach $coach): string
    {
        if (filled($coach->stripe_account_id)) {
            return (string) $coach->stripe_account_id;
        }

        $user = $coach->user;
        $account = $this->client()->accounts->create([
            'type' => 'express',
            'country' => config('services.stripe.country', 'US'),
            'email' => $user?->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'coach_id' => (string) $coach->id,
                'user_id' => (string) ($user?->id ?? ''),
            ],
        ]);

        $coach->forceFill(['stripe_account_id' => $account->id])->save();

        return $account->id;
    }

    public function createAccountOnboardingLink(Coach $coach): string
    {
        $accountId = $this->ensureConnectAccount($coach);

        $link = $this->client()->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => route('coach.stripe.refresh'),
            'return_url' => route('coach.stripe.return'),
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function createAccountLoginLink(Coach $coach): ?string
    {
        if (! filled($coach->stripe_account_id) || ! $coach->stripe_details_submitted) {
            return null;
        }

        try {
            $link = $this->client()->accounts->createLoginLink($coach->stripe_account_id);

            return $link->url;
        } catch (ApiErrorException $e) {
            Log::warning('Stripe login link failed', ['coach_id' => $coach->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function syncConnectAccount(Coach $coach): Coach
    {
        if (! filled($coach->stripe_account_id)) {
            return $coach;
        }

        $account = $this->client()->accounts->retrieve($coach->stripe_account_id);
        $charges = (bool) ($account->charges_enabled ?? false);
        $payouts = (bool) ($account->payouts_enabled ?? false);
        $submitted = (bool) ($account->details_submitted ?? false);

        $coach->forceFill([
            'stripe_charges_enabled' => $charges,
            'stripe_payouts_enabled' => $payouts,
            'stripe_details_submitted' => $submitted,
            'stripe_onboarded_at' => ($charges && $payouts)
                ? ($coach->stripe_onboarded_at ?? now())
                : $coach->stripe_onboarded_at,
        ])->save();

        return $coach->fresh();
    }

    public function applyPaymentToBooking(Booking $booking, \Stripe\PaymentIntent $intent): void
    {
        $amount = ((int) $intent->amount) / 100;
        $fee = ((int) ($intent->application_fee_amount ?? ($intent->metadata['platform_fee_cents'] ?? 0))) / 100;

        $booking->forceFill([
            'amount' => $amount,
            'stripe_payment_intent_id' => $intent->id,
            'platform_fee_amount' => $fee,
            'coach_payout_amount' => max(0, $amount - $fee),
            'payment_status' => $intent->status === 'succeeded' ? 'paid' : 'pending',
            'status' => $intent->status === 'succeeded' ? 'confirmed' : $booking->status,
        ])->save();
    }

    /**
     * Partial refund on a booking's PaymentIntent. Destination charges also pull the
     * coach's transfer back so the platform isn't left covering it.
     */
    public function refundPaymentIntent(string $paymentIntentId, int $amountCents, array $metadata = []): ?\Stripe\Refund
    {
        if ($amountCents <= 0) {
            return null;
        }

        $intent = $this->retrievePaymentIntent($paymentIntentId);
        $params = [
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
            'metadata' => $metadata,
        ];

        if (! empty($intent->transfer_data?->destination)) {
            $params['reverse_transfer'] = true;
        }

        return $this->client()->refunds->create($params);
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
