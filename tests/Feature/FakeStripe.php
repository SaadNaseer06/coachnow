<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeService;
use Stripe\Exception\ApiConnectionException;
use Stripe\PaymentIntent;
use Stripe\Refund;

/**
 * Stripe without the network.
 */
class FakeStripe extends StripeService
{
    public bool $on = true;

    public bool $refundsFail = false;

    /** @var array<string, PaymentIntent> */
    public array $intents = [];

    /** @var list<array{0: string, 1: int}> */
    public array $refunds = [];

    /** @var list<string> */
    private array $keys = [];

    public function enabled(): bool
    {
        return $this->on;
    }

    public function createFeePaymentIntent(User $payer, int $amountCents, string $description, array $metadata = []): PaymentIntent
    {
        $id = 'pi_'.(count($this->intents) + 1);

        return $this->intents[$id] = PaymentIntent::constructFrom(['id' => $id, 'amount' => $amountCents, 'amount_received' => 0, 'status' => 'requires_payment_method', 'client_secret' => $id.'_secret']);
    }

    public function createCardSession(User $user): string
    {
        return 'cuss_secret';
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        return $this->intents[$id];
    }

    public function refundPaymentIntent(string $paymentIntentId, int $amountCents, array $metadata = [], ?string $idempotencyKey = null): ?Refund
    {
        if ($this->refundsFail) {
            throw ApiConnectionException::factory('Stripe is down');
        }
        if (! in_array($idempotencyKey, $this->keys, true)) {
            $this->keys[] = $idempotencyKey;
            $this->refunds[] = [$paymentIntentId, $amountCents];
        }

        return null;
    }

    public function pay(string $id): void
    {
        $this->intents[$id] = PaymentIntent::constructFrom(['status' => 'succeeded', 'amount_received' => $this->intents[$id]->amount] + $this->intents[$id]->toArray());
    }

    public function lastIntent(): PaymentIntent
    {
        return end($this->intents);
    }
}
