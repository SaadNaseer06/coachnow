<?php

namespace App\Http\Controllers;

use App\Models\BookingCheckout;
use App\Services\BookingCheckoutService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe): Response
    {
        if (! $stripe->enabled()) {
            return response('Stripe disabled', 503);
        }

        $payload = $request->getContent();
        $secret = (string) config('services.stripe.webhook_secret');
        $event = null;

        // Without the secret anyone could post a fake "paid" event.
        if ($secret === '') {
            Log::error('Stripe webhook rejected: STRIPE_WEBHOOK_SECRET is not set.');

            return response('Webhook secret not configured', 503);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature failed', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        } catch (\Throwable $e) {
            return response('Invalid payload', 400);
        }

        $type = is_object($event) ? ($event->type ?? null) : null;
        $object = is_object($event) ? ($event->data->object ?? null) : null;

        try {
            match ($type) {
                'payment_intent.succeeded' => $this->handlePaymentSucceeded($object),
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted' => $this->handleSubscription($object, $stripe),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);
            Log::error('Stripe webhook handler error', ['type' => $type, 'error' => $e->getMessage()]);

            // A paid fee must end in a booking or a refund, so have Stripe send it again.
            if ($type === 'payment_intent.succeeded') {
                return response('Try again', 500);
            }
        }

        return response('ok', 200);
    }

    /**
     * Books the sessions if the parent's browser never came back after paying the fee.
     */
    private function handlePaymentSucceeded(mixed $intent): void
    {
        $id = is_object($intent) ? ($intent->id ?? null) : null;
        if (! $id || ! BookingCheckout::query()->where('stripe_payment_intent_id', $id)->exists()) {
            return;
        }

        $checkout = app(BookingCheckoutService::class)->complete($id);
        if ($checkout->status === BookingCheckout::REFUND_DUE) {
            throw new \RuntimeException('Booking fee refund still due for '.$id);
        }
    }

    /**
     * Keep the parent Family membership in sync (renewals, failed payments, cancellations).
     */
    private function handleSubscription(mixed $object, StripeService $stripe): void
    {
        if (! is_object($object)) {
            return;
        }

        $subscription = $object instanceof \Stripe\Subscription
            ? $object
            : \Stripe\Subscription::constructFrom(json_decode(json_encode($object), true));

        $stripe->applySubscription($subscription);
    }
}
