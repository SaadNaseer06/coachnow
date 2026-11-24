<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

/**
 * Book Now: parents pay the coach directly. CoachNow charges non-members its fee per session,
 * and this makes sure a paid fee always ends in a booking or a refund.
 */
class BookingCheckoutService
{
    public function __construct(
        private SessionBookingService $bookings,
        private StripeService $stripe,
        private AppMailer $mailer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'coach' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'string', 'max:10'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'session_type' => ['nullable', 'string', 'max:160'],
            'group_session_id' => ['nullable', 'integer'],
            'new_group' => ['nullable', 'boolean'],
            // Several 1-on-1 sessions in one checkout (multi-date / weekly series).
            'slots' => ['nullable', 'array', 'min:1', 'max:'.(int) config('coachnow.booking.max_sessions_per_checkout', 24)],
            'slots.*.date' => ['required_with:slots', 'date', 'after_or_equal:today'],
            'slots.*.time' => ['required_with:slots', 'date_format:H:i'],
            'slots.*.location_id' => ['required_with:slots', 'integer', 'exists:locations,id'],
            'slots.*.duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'recurring' => ['nullable', 'boolean'],
            'players' => ['nullable', 'array', 'min:1', 'max:30'],
            'players.*' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * Check the picks can be booked right now and price them.
     *
     * @param  array<string, mixed>  $data  Validated with rules().
     * @return array{kind: string, payload: array<string, mixed>, quote: array<string, mixed>}
     */
    public function prepare(User $user, Coach $coach, array $data): array
    {
        $players = $this->playerNames($data, $user);

        if (! empty($data['group_session_id'])) {
            $group = CoachGroupSession::query()->with('coach')->find((int) $data['group_session_id']);
            if (! $group) {
                throw ValidationException::withMessages(['slot' => 'That group session no longer exists.']);
            }
            $this->bookings->assertGroupJoinable($coach, $group, $user, $players);

            return [
                'kind' => 'group_join',
                'payload' => ['group_session_id' => $group->id, 'players' => $players],
                'quote' => $this->bookings->groupCheckoutQuote($group, count($players), $user),
            ];
        }

        if (! empty($data['slots'])) {
            $this->bookings->assertSlotsBookable($coach, $data['slots']);

            return [
                'kind' => 'multi',
                'payload' => [
                    'slots' => array_values($data['slots']),
                    'recurring' => (bool) ($data['recurring'] ?? false),
                    'session_type' => $data['session_type'] ?? null,
                ],
                'quote' => $this->bookings->multiSessionQuote($coach, count($data['slots']), $user),
            ];
        }

        $slot = [
            'date' => Carbon::parse($data['date'])->toDateString(),
            'time' => substr((string) $data['time'], 0, 5),
            'location_id' => (int) $data['location_id'],
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 60),
        ];

        if (! empty($data['new_group'])) {
            $this->bookings->assertNewGroupBookable($coach, $slot, $players);

            return [
                'kind' => 'private_group',
                'payload' => ['slot' => $slot, 'players' => $players],
                'quote' => $this->bookings->newGroupCheckoutQuote($coach, count($players), $user),
            ];
        }

        $this->bookings->assertSlotsBookable($coach, [$slot]);

        return [
            'kind' => 'single',
            'payload' => ['slot' => $slot, 'session_type' => $data['session_type'] ?? null],
            'quote' => $this->bookings->multiSessionQuote($coach, 1, $user),
        ];
    }

    /**
     * Start paying the CoachNow fee. Members and free sessions owe nothing, so they book without it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function start(User $user, Coach $coach, array $data): array
    {
        $prepared = $this->prepare($user, $coach, $data);
        $quote = $prepared['quote'];
        $feeCents = (int) round($quote['convenience_fee'] * 100);

        $summary = [
            'requires_payment' => $feeCents > 0,
            'sessions' => $quote['sessions'],
            'session_amount' => $quote['session_amount'],
            'fee_per_session' => $quote['fee_per_session'],
            'convenience_fee' => $quote['convenience_fee'],
            'amount' => $feeCents / 100,
            'currency' => config('services.stripe.currency', 'usd'),
        ];
        if ($feeCents === 0) {
            return $summary;
        }

        $count = (int) $quote['sessions'];
        $intent = $this->stripe->createFeePaymentIntent(
            $user,
            $feeCents,
            'CoachNow booking fee: '.$count.' session'.($count === 1 ? '' : 's').' with '.$coach->display_name,
            [
                'purpose' => 'booking_fee',
                'user_id' => (string) $user->id,
                'coach_id' => (string) $coach->id,
                'sessions' => (string) $count,
            ]
        );

        BookingCheckout::query()->create([
            'user_id' => $user->id,
            'coach_id' => $coach->id,
            'kind' => $prepared['kind'],
            'payload' => $prepared['payload'],
            'sessions' => $count,
            'fee_per_session' => $quote['fee_per_session'],
            'fee_cents' => $feeCents,
            'stripe_payment_intent_id' => $intent->id,
        ]);

        return $summary + [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'customer_session_client_secret' => $this->stripe->createCardSession($user),
        ];
    }

    /**
     * Turn a paid fee into bookings. The parent's browser and Stripe's webhook both call this;
     * whichever comes first books, and the other gets the same result. If the time was taken in
     * the meantime, the fee is refunded.
     */
    public function complete(string $paymentIntentId, ?User $user = null): BookingCheckout
    {
        $checkout = BookingCheckout::query()->where('stripe_payment_intent_id', $paymentIntentId)->first();
        if (! $checkout || ($user && (int) $checkout->user_id !== (int) $user->id)) {
            throw ValidationException::withMessages(['payment' => 'That payment does not belong to this account.']);
        }

        if ($checkout->status === BookingCheckout::PENDING) {
            $intent = $this->stripe->retrievePaymentIntent($paymentIntentId);
            if ($intent->status !== 'succeeded' || (int) $intent->amount_received !== (int) $checkout->fee_cents) {
                throw ValidationException::withMessages(['payment' => 'Payment has not completed yet.']);
            }

            $created = [];
            $checkout = DB::transaction(function () use ($checkout, &$created) {
                $created = [];
                $locked = BookingCheckout::query()->with(['user', 'coach'])->whereKey($checkout->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== BookingCheckout::PENDING) {
                    return $locked;
                }

                try {
                    $created = DB::transaction(fn () => $this->book($locked));
                    $locked->update([
                        'status' => BookingCheckout::BOOKED,
                        'booking_ids' => array_map(fn (Booking $b) => $b->id, $created),
                    ]);
                } catch (ValidationException $e) {
                    $created = [];
                    $locked->update([
                        'status' => BookingCheckout::REFUND_DUE,
                        'message' => mb_substr((string) (collect($e->errors())->flatten()->first() ?: 'That time is no longer available.'), 0, 250),
                    ]);
                }

                return $locked;
            }, 3);

            if ($created !== []) {
                $checkout->coach->forceFill(['last_active_at' => now()])->save();
                $this->mailer->notifyBookingCreated($created, self::mailKind($checkout->kind), $checkout->user);
            }
        }

        if ($checkout->status === BookingCheckout::REFUND_DUE) {
            $this->refund($checkout);
        }

        return $checkout;
    }

    public static function mailKind(string $kind): string
    {
        return in_array($kind, ['group_join', 'private_group'], true) ? $kind : 'single';
    }

    /**
     * @return list<Booking>
     */
    private function book(BookingCheckout $checkout): array
    {
        $coach = $checkout->coach;
        $user = $checkout->user;
        $payload = $checkout->payload;
        $intentId = $checkout->stripe_payment_intent_id;
        // What was actually paid, even if the parent became a member in between.
        $fee = (float) $checkout->fee_per_session;

        if ($coach->status !== 'active') {
            throw ValidationException::withMessages(['coach' => 'That coach is not available any more.']);
        }

        return match ($checkout->kind) {
            'group_join' => $this->bookings->joinGroupSession($coach, $user, (int) $payload['group_session_id'], $payload['players'], $intentId, $fee),
            'private_group' => $this->bookings->createPrivateGroupBooking($coach, $user, $payload['slot'], $payload['players'], $intentId, $fee),
            'multi' => $this->bookings->createMultipleDirectBookings($coach, $user, $payload['slots'], (bool) ($payload['recurring'] ?? false), $intentId, $payload['session_type'] ?? null, $fee),
            default => [$this->bookings->createDirectBooking($coach, $user, $payload['slot'] + ['session_type' => $coach->resolveSessionType($payload['session_type'] ?? null)], $intentId, $fee)],
        };
    }

    /**
     * Stripe ignores a repeat with the same key, so a retry can't refund twice.
     */
    private function refund(BookingCheckout $checkout): void
    {
        try {
            $this->stripe->refundPaymentIntent(
                $checkout->stripe_payment_intent_id,
                (int) $checkout->fee_cents,
                ['reason' => 'slot_unavailable'],
                'checkout-refund-'.$checkout->id
            );
            $checkout->update(['status' => BookingCheckout::REFUNDED]);
        } catch (ApiErrorException $e) {
            // Left as refund_due: Stripe's webhook retry tries again.
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function playerNames(array $data, User $user): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $data['players'] ?? []
        ))));

        return $names !== [] ? $names : [$user->name];
    }
}
