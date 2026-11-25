<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\CoachNewBookingMail;
use App\Mail\SeriesCancelledMail;
use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\Location;
use App\Models\User;
use App\Services\SessionBookingService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Stripe\Exception\ApiConnectionException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Tests\TestCase;

/**
 * Lee's option 2: parents pay coaches directly, Stripe only takes the $2 fee per session from
 * non-members, members pay nothing, and a paid fee always ends in a booking or a refund.
 */
class BookNowDirectPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAYS = ['2026-11-30', '2026-12-07', '2026-12-14', '2026-12-21'];

    private Location $park;

    private Coach $coach;

    private User $parent;

    private FakeStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-25 09:00:00', config('app.timezone')));
        // These cover the normal $2 fee, so the launch offer is switched off here (FeeWaiverTest covers it).
        config(['coachnow.subscription.gate_features' => true, 'coachnow.payments.sample_mode' => false, 'coachnow.payments.convenience_fee' => 2, 'coachnow.payments.fee_waived_until' => null]);

        $this->stripe = new FakeStripe;
        $this->app->instance(StripeService::class, $this->stripe);

        $this->park = Location::query()->create(['name' => 'Sommers Bend', 'slug' => 'sommers-bend', 'area' => 'Temecula, CA', 'distance_miles' => 1, 'status' => 'live']);

        $user = new User;
        $user->forceFill(['name' => 'Casey Coach', 'email' => 'casey@example.test', 'password' => 'password', 'role' => 'coach', 'phone' => '(951) 555-0199', 'email_verified_at' => now()])->save();
        // No Stripe account: coaches don't need one any more.
        $this->coach = new Coach;
        $this->coach->forceFill([
            'user_id' => $user->id, 'location_id' => $this->park->id, 'display_name' => 'Casey Coach', 'slug' => 'casey', 'status' => 'active', 'plan' => 'founding',
            'rate' => 40, 'group_rate' => 25, 'max_group_size' => 6, 'payment_instructions' => 'Venmo @casey-coach',
        ])->save();
        $this->coach->availabilitySlots()->create([
            'location_id' => $this->park->id, 'day_of_week' => 1, 'start_time' => '16:00', 'end_time' => '19:00', 'duration_minutes' => 60,
        ]);

        $this->parent = $this->family('Pat Parent');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function family(string $name, bool $member = false): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name, 'email' => Str::slug($name).'-'.Str::random(5).'@example.test', 'password' => 'password', 'role' => 'athlete',
            'phone' => '(951) 555-0100', 'email_verified_at' => now(),
            'subscription_status' => $member ? 'active' : null, 'subscription_ends_at' => $member ? now()->addMonth() : null,
        ])->save();

        return $user;
    }

    private function slot(string $date = self::MONDAYS[0], string $time = '16:00'): array
    {
        return ['date' => $date, 'time' => $time, 'location_id' => $this->park->id, 'duration_minutes' => 60];
    }

    private function fourWeeks(): array
    {
        return ['coach' => 'casey', ...$this->slot(), 'slots' => array_map(fn ($d) => $this->slot($d), self::MONDAYS), 'recurring' => true, 'session_type' => 'Private 1-on-1'];
    }

    private function group(array $overrides = []): CoachGroupSession
    {
        return CoachGroupSession::query()->create(array_merge([
            'coach_id' => $this->coach->id, 'location_id' => $this->park->id, 'session_type' => 'Small Group',
            'session_date' => self::MONDAYS[0], 'session_time' => '17:00', 'duration_minutes' => 60, 'max_players' => 6,
            'status' => 'open', 'is_public' => true, 'age_range' => 'Ages 8-12', 'price_per_player' => 25,
        ], $overrides));
    }

    private function startPayment(array $body, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->parent)->postJson('/api/stripe/booking-intent', $body);
    }

    private function confirm(string $intentId, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->parent)->postJson('/api/stripe/confirm-booking', ['payment_intent_id' => $intentId]);
    }

    /** Start, pay and confirm. */
    private function payAndBook(array $body, ?User $user = null): TestResponse
    {
        $id = $this->startPayment($body, $user)->assertOk()->json('data.payment_intent_id');
        $this->stripe->pay($id);

        return $this->confirm($id, $user);
    }

    private function webhook(string $intentId): TestResponse
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $body = json_encode(['id' => 'evt_'.Str::random(8), 'object' => 'event', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => $intentId, 'object' => 'payment_intent']]]);
        $time = time();

        return $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$time},v1=".hash_hmac('sha256', "{$time}.{$body}", 'whsec_test'),
        ], $body);
    }

    public function test_a_non_member_pays_only_the_two_dollar_fee_by_card_and_the_coach_directly(): void
    {
        $this->startPayment(['coach' => 'casey', ...$this->slot()])->assertOk()
            ->assertJsonPath('data.requires_payment', true)
            ->assertJsonPath('data.amount', 2)
            ->assertJsonPath('data.session_amount', 40)
            ->assertJsonPath('data.customer_session_client_secret', 'cuss_secret');
        $this->assertSame(200, $this->stripe->lastIntent()->amount, 'Stripe must only charge the fee, never the session.');

        $this->stripe->pay($this->stripe->lastIntent()->id);
        $this->confirm($this->stripe->lastIntent()->id)->assertCreated()
            ->assertJsonPath('data.due_to_coach', 40)
            ->assertJsonPath('data.convenience_fee', 2)
            ->assertJsonPath('data.how_to_pay', 'Venmo @casey-coach')
            ->assertJsonPath('data.coach_phone', '(951) 555-0199');

        $booking = Booking::query()->sole();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame(Booking::PAID_DIRECTLY, $booking->payment_status);
        $this->assertSame('paid', $booking->fee_status);
        $this->assertEquals(40, $booking->amount);
        $this->assertEquals(2, $booking->platform_fee_amount);
        $this->assertEquals(40, $booking->coach_payout_amount);
    }

    public function test_four_weekly_sessions_are_eight_dollars_in_one_checkout(): void
    {
        $this->payAndBook($this->fourWeeks())->assertCreated()
            ->assertJsonPath('data.sessions', 4)
            ->assertJsonPath('data.series', true)
            ->assertJsonPath('data.convenience_fee', 8)
            ->assertJsonPath('data.due_to_coach', 160);

        $this->assertCount(1, $this->stripe->intents, 'One checkout.');
        $this->assertSame(800, $this->stripe->lastIntent()->amount);
        $this->assertSame(4, Booking::query()->count());
        $this->assertSame([2.0], Booking::query()->get()->map(fn ($b) => (float) $b->platform_fee_amount)->unique()->values()->all());
        $this->assertSame(1, Booking::query()->distinct()->count('stripe_payment_intent_id'));
    }

    public function test_members_pay_no_fee_and_never_see_a_payment(): void
    {
        $member = $this->family('Mia Member', member: true);

        $this->startPayment(['coach' => 'casey', ...$this->slot()], $member)->assertOk()->assertJsonPath('data.requires_payment', false);
        $this->actingAs($member)->postJson('/api/bookings/multi', ['coach' => 'casey', 'slots' => array_map(fn ($d) => $this->slot($d), self::MONDAYS), 'recurring' => true])
            ->assertCreated()->assertJsonPath('data.convenience_fee', 0)->assertJsonPath('data.due_to_coach', 160);

        $this->assertSame([], $this->stripe->intents);
        $this->assertSame(['waived'], Booking::query()->pluck('fee_status')->unique()->all());
    }

    public function test_a_family_pays_one_fee_per_group_session_whatever_the_number_of_players(): void
    {
        $group = $this->group();
        $this->payAndBook(['coach' => 'casey', ...$this->slot(time: '17:00'), 'group_session_id' => $group->id, 'players' => ['Ana', 'Ben', 'Cal']])
            ->assertCreated()->assertJsonPath('data.players', 3)->assertJsonPath('data.convenience_fee', 2)->assertJsonPath('data.due_to_coach', 75);
        $this->assertSame(200, $this->stripe->lastIntent()->amount);

        $this->payAndBook(['coach' => 'casey', ...$this->slot(self::MONDAYS[1]), 'new_group' => true, 'players' => ['Ana', 'Ben']])
            ->assertCreated()->assertJsonPath('data.convenience_fee', 2)->assertJsonPath('data.due_to_coach', 50);
        $this->assertSame(200, $this->stripe->lastIntent()->amount);
    }

    public function test_adding_a_player_to_the_same_group_session_is_not_charged_again(): void
    {
        $group = $this->group();
        $body = ['coach' => 'casey', ...$this->slot(time: '17:00'), 'group_session_id' => $group->id];
        $this->payAndBook($body + ['players' => ['Ana']])->assertCreated();

        $this->startPayment($body + ['players' => ['Ben']])->assertOk()->assertJsonPath('data.requires_payment', false);
        $this->actingAs($this->parent)->postJson("/api/group-sessions/{$group->id}/join", ['players' => ['Ben']])->assertCreated()->assertJsonPath('data.convenience_fee', 0);
        $this->assertCount(1, $this->stripe->intents);

        // Another family still pays its own fee.
        $this->startPayment($body + ['players' => ['Zoe']], $this->family('Zoe Family'))->assertJsonPath('data.amount', 2);
    }

    public function test_free_sessions_have_no_fee(): void
    {
        $group = $this->group(['price_per_player' => 0]);

        $this->startPayment(['coach' => 'casey', ...$this->slot(time: '17:00'), 'group_session_id' => $group->id, 'players' => ['Ana']])
            ->assertOk()->assertJsonPath('data.requires_payment', false);
        $this->actingAs($this->parent)->postJson("/api/group-sessions/{$group->id}/join", ['players' => ['Ana']])->assertCreated();
        $this->assertSame([], $this->stripe->intents);
    }

    public function test_the_fee_cannot_be_skipped(): void
    {
        $group = $this->group();
        $this->actingAs($this->parent);

        $this->postJson('/api/bookings', ['coach' => 'casey', ...$this->slot()])->assertStatus(422)->assertJsonPath('requires_stripe', true);
        $this->postJson('/api/bookings', ['coach' => 'casey', ...$this->slot(), 'new_group' => true, 'players' => ['A', 'B']])->assertStatus(422);
        $this->postJson('/api/bookings/multi', ['coach' => 'casey', 'slots' => [$this->slot()]])->assertStatus(422);
        $this->postJson("/api/group-sessions/{$group->id}/join", ['players' => ['A']])->assertStatus(422);

        // Missing keys on a live site is a setup problem, never free bookings.
        $this->stripe->on = false;
        $this->postJson('/api/bookings', ['coach' => 'casey', ...$this->slot()])->assertStatus(503);
        $this->startPayment(['coach' => 'casey', ...$this->slot()])->assertStatus(503);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_nothing_is_booked_until_the_payment_succeeds_or_for_someone_elses_payment(): void
    {
        $id = $this->startPayment(['coach' => 'casey', ...$this->slot()])->json('data.payment_intent_id');
        $this->confirm($id)->assertStatus(422);
        $this->assertSame(0, Booking::query()->count());

        $this->stripe->pay($id);
        $this->confirm($id, $this->family('Sam Stranger'))->assertStatus(422);
        $this->confirm('pi_made_up')->assertStatus(422);
        $this->assertSame(0, Booking::query()->count());
    }

    public function test_confirming_twice_books_and_emails_once(): void
    {
        $id = $this->startPayment(['coach' => 'casey', ...$this->slot()])->json('data.payment_intent_id');
        $this->stripe->pay($id);

        $first = $this->confirm($id)->assertCreated()->json('data.reference');
        $second = $this->confirm($id)->assertCreated()->json('data.reference');
        $this->webhook($id)->assertOk();

        $this->assertSame($first, $second);
        $this->assertSame(1, Booking::query()->count());
        Mail::assertSent(BookingConfirmedMail::class, 1);
        Mail::assertSent(CoachNewBookingMail::class, 1);
    }

    public function test_the_booking_is_made_even_if_the_browser_closes_after_paying(): void
    {
        $id = $this->startPayment($this->fourWeeks())->json('data.payment_intent_id');
        $this->stripe->pay($id);

        $this->webhook($id)->assertOk();

        $this->assertSame(4, Booking::query()->count());
        $this->assertSame(BookingCheckout::BOOKED, BookingCheckout::query()->sole()->status);
        Mail::assertSent(BookingConfirmedMail::class, 1);
    }

    public function test_the_fee_comes_back_if_the_time_was_taken_while_paying(): void
    {
        $id = $this->startPayment(['coach' => 'casey', ...$this->slot()])->json('data.payment_intent_id');
        $this->payAndBook(['coach' => 'casey', ...$this->slot()], $this->family('Quick Family'))->assertCreated();

        $this->stripe->pay($id);
        $this->assertStringContainsString('has been refunded', $this->confirm($id)->assertStatus(422)->json('message'));
        $this->confirm($id)->assertStatus(422);
        $this->webhook($id)->assertOk();

        $this->assertSame([[$id, 200]], $this->stripe->refunds, 'Refunded once, in full.');
        $this->assertSame(1, Booking::query()->count());
        Mail::assertSent(BookingConfirmedMail::class, 1);
    }

    public function test_one_taken_date_refunds_the_whole_checkout_rather_than_booking_part_of_it(): void
    {
        $id = $this->startPayment($this->fourWeeks())->json('data.payment_intent_id');
        $this->payAndBook(['coach' => 'casey', ...$this->slot(self::MONDAYS[2])], $this->family('Quick Family'))->assertCreated();

        $this->stripe->pay($id);
        $this->confirm($id)->assertStatus(422);

        $this->assertSame([[$id, 800]], $this->stripe->refunds);
        $this->assertSame(0, Booking::query()->where('athlete_id', $this->parent->id)->count());
    }

    public function test_a_refund_that_fails_is_tried_again_by_the_webhook(): void
    {
        $id = $this->startPayment(['coach' => 'casey', ...$this->slot()])->json('data.payment_intent_id');
        $this->payAndBook(['coach' => 'casey', ...$this->slot()], $this->family('Quick Family'));
        $this->stripe->pay($id);

        $this->stripe->refundsFail = true;
        $this->assertStringContainsString('will be refunded', $this->confirm($id)->assertStatus(422)->json('message'));
        $this->webhook($id)->assertStatus(500);

        $this->stripe->refundsFail = false;
        $this->webhook($id)->assertOk();
        $this->assertSame([[$id, 200]], $this->stripe->refunds);
        $this->assertSame(BookingCheckout::REFUNDED, BookingCheckout::query()->where('stripe_payment_intent_id', $id)->sole()->status);
    }

    public function test_a_parent_cancelling_gets_no_card_refund_and_is_told_to_ask_the_coach(): void
    {
        $this->payAndBook(['coach' => 'casey', ...$this->slot()]);
        $booking = Booking::query()->sole();

        $this->actingAs($this->parent)->postJson("/api/bookings/{$booking->reference}/cancel")->assertOk()
            ->assertJsonPath('message', 'Booking cancelled. If you’ve already paid your coach, contact them about a refund.');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame([], $this->stripe->refunds);
        Mail::assertSent(BookingCancelledMail::class, fn ($m) => $m->audience === 'parent' && $m->feeRefund === 0.0 && str_contains($m->render(), 'contact them about a refund'));
        Mail::assertSent(BookingCancelledMail::class, fn ($m) => $m->audience === 'coach' && str_contains($m->render(), 'arrange any refund with them directly'));
    }

    public function test_the_fee_is_refunded_when_the_coach_cancels(): void
    {
        $this->payAndBook(['coach' => 'casey', ...$this->slot()]);
        $booking = Booking::query()->sole();

        $this->actingAs($this->coach->user)->deleteJson("/coach/schedule/sessions/{$booking->id}")->assertOk();

        $this->assertSame([[$booking->stripe_payment_intent_id, 200]], $this->stripe->refunds);
        $this->assertSame('refunded', $booking->fresh()->fee_status);
        Mail::assertSent(BookingCancelledMail::class, fn ($m) => $m->audience === 'coach_cancelled' && $m->hasTo($this->parent->email) && $m->feeRefund === 2.0 && str_contains($m->render(), 'cancelled this session'));
    }

    public function test_cancelling_a_series_refunds_the_fee_for_each_remaining_session(): void
    {
        $this->payAndBook($this->fourWeeks());
        $series = Booking::query()->value('series_id');

        $this->actingAs($this->coach->user)->postJson("/coach/schedule/series/{$series}/cancel")->assertOk();

        $this->assertSame(800, array_sum(array_column($this->stripe->refunds, 1)));
        Mail::assertSent(SeriesCancelledMail::class, fn ($m) => (float) $m->refund === 8.0);
    }

    public function test_a_stripe_problem_never_stops_a_coach_cancelling(): void
    {
        $this->payAndBook(['coach' => 'casey', ...$this->slot()]);
        $booking = Booking::query()->sole();
        $this->stripe->refundsFail = true;

        $this->actingAs($this->coach->user)->deleteJson("/coach/schedule/sessions/{$booking->id}")->assertOk();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->fee_status);
    }

    public function test_past_sessions_and_a_switched_off_policy_refund_nothing(): void
    {
        $this->payAndBook(['coach' => 'casey', ...$this->slot()]);
        $this->payAndBook(['coach' => 'casey', ...$this->slot(self::MONDAYS[1])]);
        [$past, $future] = Booking::query()->orderBy('session_date')->get()->all();
        $bookings = app(SessionBookingService::class);

        config(['coachnow.payments.refund_fee_on_coach_cancel' => false]);
        $bookings->cancelCoachBooking($this->coach, $future);
        config(['coachnow.payments.refund_fee_on_coach_cancel' => true]);
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', config('app.timezone')));
        $bookings->cancelCoachBooking($this->coach, $past);

        $this->assertSame([], $this->stripe->refunds);
    }

    public function test_removing_one_player_keeps_the_fee_until_the_family_has_no_place_left(): void
    {
        $group = $this->group();
        $this->payAndBook(['coach' => 'casey', ...$this->slot(time: '17:00'), 'group_session_id' => $group->id, 'players' => ['Ana', 'Ben']]);
        [$ana, $ben] = Booking::query()->orderBy('id')->get()->all();
        $bookings = app(SessionBookingService::class);

        $bookings->removePlayerFromGroup($this->coach, $ana);
        $this->assertSame([], $this->stripe->refunds);
        $this->assertSame('confirmed', $ben->fresh()->status);

        $bookings->cancelCoachBooking($this->coach, $ben);
        $this->assertSame([[$ana->stripe_payment_intent_id, 200]], $this->stripe->refunds, 'The last place is gone, so the family gets its fee back, once.');
    }

    public function test_coaches_set_how_they_are_paid_and_need_no_stripe_account(): void
    {
        $this->coach->forceFill(['payment_instructions' => null])->save();
        $this->actingAs($this->coach->user)->get('/coach/dashboard')->assertOk()->assertSee('Tell families how to pay you');

        $profile = $this->get('/coach/profile')->assertOk()->assertSee('How families pay you')->assertDontSee('Payouts')->assertDontSee('Set up payouts with Stripe');
        $this->put('/coach/profile', [
            'display_name' => 'Casey Coach', 'phone' => '(951) 555-0199', 'sports' => [User::SPORTS[0]], 'specialties' => [Coach::SPECIALTIES[0]],
            'experience' => Coach::EXPERIENCE_OPTIONS[0], 'ages' => '8-12', 'location_id' => $this->park->id, 'rate' => 40,
            'payment_instructions' => "Zelle casey@example.test\nor cash",
        ])->assertSessionHasNoErrors();

        $this->assertSame("Zelle casey@example.test\nor cash", $this->coach->fresh()->payment_instructions);
        $this->get('/coach/dashboard')->assertDontSee('Tell families how to pay you');
        $this->get('/coach/stripe/onboard')->assertNotFound();
    }

    public function test_only_the_family_that_booked_sees_how_to_pay(): void
    {
        $this->payAndBook(['coach' => 'casey', ...$this->slot()]);

        $this->actingAs($this->parent)->get('/player-dashboard')->assertOk()->assertSee('Pay your coach · $40', false)->assertSee('Venmo @casey-coach')->assertSee('(951) 555-0199');
        $this->actingAs($this->family('Other Family'))->get('/player-dashboard')->assertOk()->assertDontSee('Venmo @casey-coach');
        $this->get('/coaches/casey')->assertOk()->assertDontSee('Venmo @casey-coach');

        Mail::assertSent(BookingConfirmedMail::class, function ($m) {
            $html = $m->render();

            return str_contains($html, 'Venmo @casey-coach') && str_contains($html, '$40.00 directly') && str_contains($html, 'CoachNow fee paid by card: $2.00');
        });
        Mail::assertSent(CoachNewBookingMail::class, function ($m) {
            $html = $m->render();

            return str_contains($html, 'They pay you directly: $40.00') && str_contains($html, '(951) 555-0100');
        });

        Booking::query()->update(['status' => 'cancelled']);
        $this->actingAs($this->parent)->get('/player-dashboard')->assertDontSee('Venmo @casey-coach');
    }

    public function test_the_booking_page_and_faq_explain_the_new_payments(): void
    {
        $this->actingAs($this->parent)->get('/book/casey')->assertOk()->assertSee('data-convenience-fee="2"', false)->assertSee('You pay Casey Coach directly')->assertSee('One CoachNow fee per session');
        $this->actingAs($this->family('Mia Member', member: true))->get('/book/casey')->assertOk()->assertSee('data-member="1"', false)->assertDontSee('One CoachNow fee per session');
        $this->get('/faq')->assertOk()->assertSee('You pay your coach directly')->assertDontSee('processed securely through the platform');
    }

    public function test_admin_sees_only_coachnow_fees_as_its_own_money(): void
    {
        $this->payAndBook($this->fourWeeks());
        Booking::query()->orderBy('id')->first()->forceFill(['fee_status' => 'refunded'])->save();

        $admin = new User;
        $admin->forceFill(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password', 'role' => 'admin', 'email_verified_at' => now()])->save();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Booking fees (MTD)')->assertViewHas('revenueMtd', 6.0);
        $this->get('/admin/bookings')->assertOk()->assertSee('CoachNow fee')->assertSee('Refunded');
    }
}
