<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    private ?StripeClient $client = null;

    public function enabled(): bool
    {
        return filled(config('services.stripe.secret'))
            && filled(config('services.stripe.key'));
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

    public function platformFeeCents(int $amountCents): int
    {
        $percent = (float) config('coachnow.payments.platform_fee_percent', 10);
        $fixed = (int) round(((float) config('coachnow.payments.platform_fee_fixed', 0)) * 100);
        $fee = (int) round($amountCents * ($percent / 100)) + $fixed;

        return max(0, min($amountCents - 1, $fee));
    }

    public function ensureCustomer(User $user): string
    {
        if (filled($user->stripe_customer_id)) {
            return (string) $user->stripe_customer_id;
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
        if (! $coach->canReceivePayouts()) {
            throw new \RuntimeException('This coach has not finished payout setup.');
        }

        $customerId = $this->ensureCustomer($payer);
        $fee = $this->platformFeeCents($amountCents);

        $params = [
            'amount' => $amountCents,
            'currency' => config('services.stripe.currency', 'usd'),
            'customer' => $customerId,
            'application_fee_amount' => $fee,
            'transfer_data' => [
                'destination' => $coach->stripe_account_id,
            ],
            'metadata' => array_merge([
                'coach_id' => (string) $coach->id,
                'user_id' => (string) $payer->id,
                'platform_fee_cents' => (string) $fee,
            ], $metadata),
        ];

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
        $fee = ((int) ($intent->application_fee_amount ?? 0)) / 100;

        $booking->forceFill([
            'amount' => $amount,
            'stripe_payment_intent_id' => $intent->id,
            'platform_fee_amount' => $fee,
            'coach_payout_amount' => max(0, $amount - $fee),
            'payment_status' => $intent->status === 'succeeded' ? 'paid' : 'pending',
            'status' => $intent->status === 'succeeded' ? 'confirmed' : $booking->status,
        ])->save();
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
