<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\SessionRequestPlayer;
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

        try {
            if ($secret !== '') {
                $event = Webhook::constructEvent(
                    $payload,
                    (string) $request->header('Stripe-Signature'),
                    $secret
                );
            } else {
                $event = json_decode($payload);
            }
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
                'account.updated' => $this->handleAccountUpdated($object, $stripe),
                'payment_intent.succeeded' => $this->handlePaymentSucceeded($object),
                'payment_intent.payment_failed' => $this->handlePaymentFailed($object),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);
            Log::error('Stripe webhook handler error', ['type' => $type, 'error' => $e->getMessage()]);
        }

        return response('ok', 200);
    }

    private function handleAccountUpdated(mixed $account, StripeService $stripe): void
    {
        $accountId = is_object($account) ? ($account->id ?? null) : null;
        if (! $accountId) {
            return;
        }

        $coach = Coach::query()->where('stripe_account_id', $accountId)->first();
        if ($coach) {
            $stripe->syncConnectAccount($coach);
        }
    }

    private function handlePaymentSucceeded(mixed $intent): void
    {
        $id = is_object($intent) ? ($intent->id ?? null) : null;
        if (! $id) {
            return;
        }

        Booking::query()
            ->where('stripe_payment_intent_id', $id)
            ->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);

        SessionRequestPlayer::query()
            ->where('stripe_payment_intent_id', $id)
            ->update(['paid' => true]);
    }

    private function handlePaymentFailed(mixed $intent): void
    {
        $id = is_object($intent) ? ($intent->id ?? null) : null;
        if (! $id) {
            return;
        }

        Booking::query()
            ->where('stripe_payment_intent_id', $id)
            ->update(['payment_status' => 'failed']);
    }
}
