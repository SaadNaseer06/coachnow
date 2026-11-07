<?php

namespace Tests\Feature;

use App\Events\SessionRequestChanged;
use App\Mail\MembershipConfirmedMail;
use App\Mail\SessionRequestAcceptedMail;
use App\Mail\SessionRequestJoinMail;
use App\Mail\SessionRequestPlaceMail;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\User;
use App\Services\SessionBookingService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Subscription;
use Tests\TestCase;

class DirectPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    private Location $park;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-10 09:00:00', config('app.timezone')));
        config([
            'coachnow.subscription.gate_features' => true,
            'coachnow.payments.sample_mode' => true,
        ]);

        $this->park = Location::query()->create([
            'name' => 'Test Park',
            'slug' => 'test-park',
            'area' => 'Temecula, CA',
            'distance_miles' => 1,
            'status' => 'live',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(string $name = 'Pat Parent', ?string $phone = '555-0100'): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::random(5).'@example.test',
            'password' => 'password',
            'role' => 'athlete',
            'phone' => $phone,
            'email_verified_at' => now(),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        return $user;
    }

    private function coach(string $name = 'Casey Coach'): Coach
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::random(5).'@example.test',
            'password' => 'password',
            'role' => 'coach',
            'phone' => '555-0199',
            'email_verified_at' => now(),
        ])->save();

        $coach = new Coach;
        $coach->forceFill([
            'user_id' => $user->id,
            'location_id' => $this->park->id,
            'display_name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'status' => 'active',
            'plan' => 'founding',
            'rate' => 40,
        ])->save();

        return $coach;
    }

    /** @param array<string, mixed> $overrides */
    private function postRequest(User $parent, array $overrides = []): SessionRequest
    {
        $response = $this->actingAs($parent)->postJson('/api/session-requests', $overrides + [
            'location_id' => 'test-park',
            'location_name' => 'Test Park',
            'session_date' => '2026-11-20',
            'session_time' => '5:00 PM',
            'session_type' => 'Skills Training — Group Session',
            'sport' => User::SPORTS[0],
            'price_range' => 'Up to $30 / player',
            'max_players' => 3,
        ])->assertCreated();

        return SessionRequest::query()->where('reference', $response->json('data.id'))->firstOrFail();
    }

    private function accept(SessionRequest $session, Coach $coach, float $price = 25): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/accept", [
            'price_per_player' => $price,
            'payment_note' => 'Venmo @casey, $10 deposit',
        ]);
    }

    private function join(SessionRequest $session, User $parent): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($parent)->postJson("/api/session-requests/{$session->reference}/join");
    }

    private function confirm(SessionRequest $session, Coach $coach, User $parent): \Illuminate\Testing\TestResponse
    {
        $player = $session->players()->where('user_id', $parent->id)->firstOrFail();

        return $this->actingAs($coach->user)
            ->postJson("/api/session-requests/{$session->reference}/players/{$player->id}/confirm");
    }

    public function test_a_member_submits_a_request_with_no_card_or_deposit(): void
    {
        $session = $this->postRequest($this->member());

        $this->assertSame('open', $session->status);
        $this->assertEquals(0, $session->deposit_amount);
        $this->assertNull($session->card_on_file);
        $this->assertSame('pending', $session->players()->first()->status);
    }

    public function test_a_nonmember_cannot_submit_or_join(): void
    {
        $nonmember = $this->member('Nora Nonmember');
        $nonmember->forceFill(['subscription_status' => null, 'subscription_ends_at' => null])->save();

        $this->actingAs($nonmember)->postJson('/api/session-requests', [
            'location_name' => 'Test Park',
            'session_date' => '2026-11-20',
            'session_type' => 'Skills Training — Group Session',
            'sport' => User::SPORTS[0],
        ])->assertForbidden();

        $this->join($this->postRequest($this->member()), $nonmember)->assertForbidden();
    }

    public function test_a_phone_number_is_needed_so_the_coach_can_reach_the_family(): void
    {
        $parent = $this->member('Noah Nophone', null);

        $this->actingAs($parent)->postJson('/api/session-requests', [
            'location_name' => 'Test Park',
            'session_date' => '2026-11-20',
            'session_type' => 'Skills Training — Group Session',
            'sport' => User::SPORTS[0],
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->postRequest($parent, ['phone' => '555-0142']);
        $this->assertSame('555-0142', $parent->fresh()->phone);
    }

    public function test_accepting_needs_a_price_and_charges_nothing(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $coach = $this->coach();

        $this->actingAs($coach->user)
            ->postJson("/api/session-requests/{$session->reference}/accept", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price_per_player');

        $this->accept($session, $coach)->assertOk();

        $session->refresh();
        $this->assertSame('awaiting_deposit', $session->status);
        $this->assertSame('$25 per player', $session->priceLabel());

        $booking = Booking::query()->where('session_request_id', $session->id)->sole();
        $this->assertSame('pending', $booking->status);
        $this->assertSame(Booking::PAID_DIRECTLY, $booking->payment_status);
        $this->assertEquals(0, $booking->platform_fee_amount);
        $this->assertNull($booking->stripe_payment_intent_id);
        $this->assertSame('Venmo @casey, $10 deposit', $coach->fresh()->payment_instructions);

        Mail::assertSent(SessionRequestAcceptedMail::class, fn ($mail) => $mail->hasTo($parent->email));
    }

    public function test_only_one_coach_can_accept_and_it_leaves_other_coaches_lists(): void
    {
        $session = $this->postRequest($this->member());
        [$first, $second] = [$this->coach('First Coach'), $this->coach('Second Coach')];

        $this->actingAs($second->user)->getJson('/api/session-requests')
            ->assertJsonPath('data.0.id', $session->reference);

        $this->accept($session, $first)->assertOk();
        $this->accept($session, $second)->assertUnprocessable();

        $this->assertSame($first->id, $session->fresh()->host_coach_id);
        $this->actingAs($second->user)->getJson('/api/session-requests')->assertJsonCount(0, 'data');
        $this->actingAs($first->user)->getJson('/api/session-requests')
            ->assertJsonPath('data.0.id', $session->reference);
    }

    public function test_every_selected_coach_gets_the_request_and_any_one_can_accept(): void
    {
        [$first, $second, $other] = [$this->coach('First Coach'), $this->coach('Second Coach'), $this->coach('Other Coach')];
        $session = $this->postRequest($this->member(), ['requested_coach_ids' => [$first->id, $second->id]]);

        $this->actingAs($second->user)->getJson('/api/session-requests')
            ->assertJsonPath('data.0.id', $session->reference)
            ->assertJsonPath('data.0.invited_coach_ids', [$first->id, $second->id])
            ->assertJsonPath('data.0.requested_coach', 'First Coach, Second Coach');
        $this->actingAs($other->user)->getJson('/api/session-requests')->assertJsonCount(0, 'data');

        $this->accept($session, $other)->assertForbidden();
        $this->accept($session, $second)->assertOk();

        $this->actingAs($first->user)->getJson('/api/session-requests')->assertJsonCount(0, 'data');
    }

    public function test_one_selected_coach_declining_leaves_the_request_for_the_others(): void
    {
        [$first, $second] = [$this->coach('First Coach'), $this->coach('Second Coach')];
        $session = $this->postRequest($this->member(), ['requested_coach_ids' => [$first->id, $second->id]]);
        $decline = fn (Coach $coach) => $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/decline");

        $decline($first)->assertOk()->assertJsonPath('hidden', true);

        $session = $session->fresh();
        $this->assertSame('open', $session->status);
        $this->assertSame($second->id, $session->requested_coach_id);
        $this->assertSame([$second->id], $session->invitedCoachIds());
        $this->actingAs($first->user)->getJson('/api/session-requests')->assertJsonCount(0, 'data');
        $this->actingAs($second->user)->getJson('/api/session-requests')->assertJsonCount(1, 'data');

        $decline($second)->assertOk();
        $this->assertSame('cancelled', $session->fresh()->status);
    }

    public function test_every_coach_hears_when_an_open_request_is_taken(): void
    {
        $channels = fn (array $request) => array_map(
            fn ($channel) => $channel->name,
            (new SessionRequestChanged($request, 'accepted'))->broadcastOn()
        );

        $this->assertEqualsCanonicalizing(
            ['private-coach.session-requests.7', 'coaches.session-requests'],
            $channels(['host_coach_id' => 7])
        );

        // Requests sent to chosen coaches stay private to them.
        $this->assertEqualsCanonicalizing(
            ['private-coach.session-requests.7', 'private-coach.session-requests.8'],
            $channels(['host_coach_id' => 7, 'requested_coach_id' => 7, 'invited_coach_ids' => [7, 8]])
        );
    }

    public function test_expired_and_past_requests_leave_the_available_list(): void
    {
        $parent = $this->member();
        $coach = $this->coach();

        $past = $this->postRequest($parent, ['session_date' => '2026-11-11', 'session_time' => '6:00 PM']);
        $deadline = $this->postRequest($parent, ['session_date' => '2026-11-12', 'know_by_at' => '2026-11-10T18:00:00.000Z']);
        $current = $this->postRequest($parent, ['session_date' => '2026-11-20']);

        $this->actingAs($coach->user)->getJson('/api/session-requests')->assertJsonCount(3, 'data');

        // 18:00 UTC is 10:00 in Los Angeles: a UTC time read as Pacific would expire 8 hours late.
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:30:00', config('app.timezone')));
        $this->actingAs($coach->user)->getJson('/api/session-requests')->assertJsonCount(2, 'data');
        $this->assertSame('expired', $deadline->fresh()->status);

        Carbon::setTestNow(Carbon::parse('2026-11-13 09:00:00', config('app.timezone')));
        $this->actingAs($coach->user)->getJson('/api/session-requests')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $current->reference);
        $this->assertSame('expired', $past->fresh()->status);
    }

    public function test_an_expired_request_cannot_be_accepted(): void
    {
        $session = $this->postRequest($this->member(), ['session_date' => '2026-11-11']);

        Carbon::setTestNow(Carbon::parse('2026-11-12 09:00:00', config('app.timezone')));
        $this->accept($session, $this->coach())->assertUnprocessable();
        $this->assertSame('expired', $session->fresh()->status);
    }

    public function test_request_to_join_is_pending_with_no_card_and_holds_no_place(): void
    {
        $session = $this->postRequest($this->member());
        $coach = $this->coach();
        $this->accept($session, $coach);
        $joiner = $this->member('Jo Joiner');

        $this->join($session, $joiner)
            ->assertOk()
            ->assertJsonPath('data.my_place', 'pending')
            ->assertJsonPath('data.coach_contact.email', $coach->user->email);

        $session = $session->fresh();
        $this->assertSame(1, $session->pendingJoinCount());
        $this->assertSame(1, $session->placesTaken());
        $this->assertSame(2, $session->spotsLeft());
        $this->assertSame(0, Booking::query()->where('athlete_id', $joiner->id)->count());

        Mail::assertSent(SessionRequestJoinMail::class, fn ($mail) => $mail->hasTo($coach->user->email));
        $this->join($session, $joiner)->assertUnprocessable();
    }

    public function test_the_page_shows_request_to_join_and_the_price_with_no_card_section(): void
    {
        $session = $this->postRequest($this->member());
        $this->accept($session, $this->coach());

        $this->actingAs($this->member('Jo Joiner'))->get("/requests/{$session->reference}")
            ->assertOk()
            ->assertSee('Request to Join')
            ->assertSee('$25 per player')
            ->assertDontSee('Pay $')
            ->assertDontSee('data-payment-root', false);
    }

    public function test_training_near_you_calls_the_coachs_price_a_price_not_a_budget(): void
    {
        $waiting = $this->postRequest($parent = $this->member(), ['session_date' => '2026-11-14']);

        $this->get('/training-near-you')->assertOk()->assertSee('Budget:')->assertDontSee('Price:');

        $this->accept($waiting, $this->coach());

        $this->get('/training-near-you')->assertOk()->assertSee('Price:')->assertSee('$25 per player')->assertDontSee('Budget:');
    }

    public function test_confirming_a_place_creates_the_booking_and_updates_the_spots(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $coach = $this->coach();
        $this->accept($session, $coach);
        $this->join($session, $joiner = $this->member('Jo Joiner'));

        $this->confirm($session, $coach, $parent)->assertOk();
        $this->assertSame('confirmed', $session->fresh()->status);
        $this->assertSame('confirmed', Booking::query()->where('athlete_id', $parent->id)->sole()->status);

        $this->confirm($session, $coach, $joiner)->assertOk();

        $booking = Booking::query()->where('athlete_id', $joiner->id)->sole();
        $this->assertSame('confirmed', $booking->status);
        $this->assertEquals(25, $booking->amount);
        $this->assertSame(1, $session->fresh()->spotsLeft());

        Mail::assertSent(SessionRequestPlaceMail::class, fn ($mail) => $mail->hasTo($joiner->email) && $mail->confirmed);
    }

    public function test_a_session_cannot_be_overbooked_by_pending_requests(): void
    {
        $session = $this->postRequest($this->member(), ['max_players' => 2]);
        $coach = $this->coach();
        $this->accept($session, $coach);

        [$a, $b] = [$this->member('Ann Alpha'), $this->member('Ben Beta')];
        $this->join($session, $a)->assertOk();
        $this->join($session, $b)->assertOk();

        $this->confirm($session, $coach, $a)->assertOk();
        $this->confirm($session, $coach, $b)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This session is full. Raise the max players or decline this request.');

        $this->assertSame(2, $session->fresh()->placesTaken());
        $this->assertSame(0, Booking::query()->where('athlete_id', $b->id)->count());
        $this->join($session, $this->member('Cal Late'))->assertUnprocessable();
    }

    public function test_raising_the_max_lets_the_coach_confirm_more_and_it_cannot_go_below_places_taken(): void
    {
        $session = $this->postRequest($this->member(), ['max_players' => 2]);
        $coach = $this->coach();
        $this->accept($session, $coach);
        $this->join($session, $a = $this->member('Ann Alpha'));
        $this->join($session, $b = $this->member('Ben Beta'));
        $this->confirm($session, $coach, $a)->assertOk();

        $patch = fn (int $max) => $this->actingAs($coach->user)
            ->patchJson("/api/session-requests/{$session->reference}", ['max_players' => $max]);

        $patch(1)->assertUnprocessable();
        $patch(3)->assertOk();
        $this->confirm($session, $coach, $b)->assertOk();
    }

    public function test_a_private_session_takes_one_player(): void
    {
        $session = $this->postRequest($this->member(), ['session_type' => 'Private 1-on-1 Session', 'max_players' => null]);
        $this->accept($session, $this->coach());

        $this->assertTrue($session->fresh()->isFull());
        $this->join($session, $this->member('Jo Joiner'))->assertUnprocessable();
    }

    public function test_a_coach_can_decline_a_join_request(): void
    {
        $session = $this->postRequest($this->member());
        $coach = $this->coach();
        $this->accept($session, $coach);
        $this->join($session, $joiner = $this->member('Jo Joiner'));
        $player = $session->players()->where('user_id', $joiner->id)->sole();

        $this->actingAs($coach->user)
            ->postJson("/api/session-requests/{$session->reference}/players/{$player->id}/decline")
            ->assertOk();

        $session = $session->fresh();
        $this->assertSame('declined', $player->fresh()->status);
        $this->assertNull($session->coachContactFor($joiner));
        Mail::assertSent(SessionRequestPlaceMail::class, fn ($mail) => $mail->hasTo($joiner->email) && ! $mail->confirmed);
    }

    public function test_only_the_hosting_coach_can_confirm(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $this->accept($session, $this->coach());

        $this->confirm($session, $this->coach('Other Coach'), $parent)->assertForbidden();
    }

    public function test_contact_details_only_reach_the_people_on_the_session(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $coach = $this->coach();
        $this->accept($session, $coach);
        $session = $session->fresh();

        // This array goes out on a public channel.
        $public = json_encode($session->toPortalArray());
        $this->assertStringNotContainsString($parent->email, $public);
        $this->assertStringNotContainsString('555-01', $public);
        $this->assertStringNotContainsString('Venmo', $public);

        $this->assertSame($parent->email, $session->toCoachPortalArray($coach)['players'][0]['email']);

        $other = json_encode($session->toCoachPortalArray($this->coach('Other Coach')));
        $this->assertStringNotContainsString($parent->email, $other);
        $this->assertNull($session->coachContactFor($this->member('Stranger Danger')));
    }

    public function test_a_family_gets_the_coach_contact_only_after_acceptance(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $this->assertNull($session->coachContactFor($parent));

        $coach = $this->coach();
        $this->accept($session, $coach);

        $this->actingAs($parent)->getJson("/api/session-requests/{$session->reference}")
            ->assertJsonPath('data.coach_contact.phone', '555-0199')
            ->assertJsonPath('data.coach_contact.payment_note', 'Venmo @casey, $10 deposit');
    }

    public function test_cancelling_a_booking_frees_the_place(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $coach = $this->coach();
        $this->accept($session, $coach);
        $this->join($session, $joiner = $this->member('Jo Joiner'));
        $this->confirm($session, $coach, $joiner);

        $booking = Booking::query()->where('athlete_id', $joiner->id)->sole();
        app(SessionBookingService::class)->removePlayerFromGroup($coach, $booking);
        $this->assertSame(2, $session->fresh()->spotsLeft());

        $requesterBooking = Booking::query()->where('athlete_id', $parent->id)->sole();
        app(SessionBookingService::class)->cancelCoachBooking($coach, $requesterBooking);
        $this->assertSame('cancelled', $session->fresh()->status);
    }

    public function test_direct_payments_are_left_out_of_admin_revenue(): void
    {
        $session = $this->postRequest($this->member());
        $this->accept($session, $this->coach());
        $admin = new User;
        $admin->forceFill([
            'name' => 'Ada Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->save();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertViewHas('revenueMtd', 0.0);
    }

    public function test_checkout_never_activates_a_membership_without_stripe_outside_sample_mode(): void
    {
        config([
            'coachnow.payments.sample_mode' => false,
            'services.stripe.secret' => null,
            'services.stripe.key' => null,
        ]);
        $parent = $this->member();
        $parent->forceFill(['subscription_status' => null, 'subscription_ends_at' => null])->save();

        $this->actingAs($parent)->post('/membership/checkout')->assertRedirect();

        $this->assertFalse($parent->fresh()->hasActiveSubscription());
    }

    public function test_sample_mode_still_activates_for_testing(): void
    {
        $parent = $this->member();
        $parent->forceFill(['subscription_status' => null, 'subscription_ends_at' => null])->save();

        $this->actingAs($parent)->post('/membership/checkout')->assertRedirect();

        $this->assertTrue($parent->fresh()->hasActiveSubscription());
    }

    public function test_the_webhook_refuses_events_without_a_signing_secret(): void
    {
        config([
            'coachnow.payments.sample_mode' => false,
            'services.stripe.secret' => 'sk_test_x',
            'services.stripe.key' => 'pk_test_x',
            'services.stripe.webhook_secret' => '',
        ]);
        $parent = $this->member();
        $parent->forceFill(['subscription_status' => null, 'subscription_ends_at' => null])->save();

        $this->postJson('/api/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_fake', 'status' => 'active', 'metadata' => ['user_id' => $parent->id]]],
        ])->assertStatus(503);

        $this->assertFalse($parent->fresh()->hasActiveSubscription());
    }

    public function test_the_membership_email_is_sent_once_per_subscription(): void
    {
        config(['coachnow.payments.sample_mode' => false]);
        $parent = $this->member();
        $parent->forceFill(['subscription_status' => null, 'subscription_ends_at' => null])->save();

        $subscription = Subscription::constructFrom([
            'id' => 'sub_123',
            'status' => 'active',
            'customer' => 'cus_123',
            'current_period_end' => now()->addMonth()->getTimestamp(),
            'cancel_at_period_end' => false,
            'metadata' => ['user_id' => (string) $parent->id],
        ]);

        // Checkout's return page and the webhook both report the same subscription.
        app(StripeService::class)->applySubscription($subscription);
        app(StripeService::class)->applySubscription($subscription);

        $this->assertTrue($parent->fresh()->hasActiveSubscription());
        Mail::assertSent(MembershipConfirmedMail::class, 1);
    }

    public function test_the_membership_page_states_the_billing_terms(): void
    {
        $parent = $this->member();
        $parent->forceFill(['subscription_status' => null, 'subscription_ends_at' => null])->save();

        $this->actingAs($parent)->get('/request-session')
            ->assertOk()
            ->assertSee('$10 per month.', false)
            ->assertSee('Renews automatically until you cancel.')
            ->assertSee('Nov 10, 2026')
            ->assertSee('Dec 10, 2026');
    }

    public function test_the_background_badge_needs_a_provider_and_date(): void
    {
        $coach = $this->coach();

        $coach->forceFill(['background_check_status' => 'clear'])->save();
        $this->assertFalse($coach->fresh()->hasVerifiedBackgroundCheck());

        $coach->forceFill(['background_check_provider' => 'NCSI', 'background_check_date' => '2026-11-03'])->save();
        $coach = $coach->fresh();
        $this->assertTrue($coach->hasVerifiedBackgroundCheck());
        $this->assertSame('NCSI · checked Nov 3, 2026', $coach->backgroundCheckSummary());
    }

    public function test_verifying_a_profile_does_not_mark_a_background_check(): void
    {
        $coach = $this->coach();
        $coach->forceFill(['verified_at' => now()])->save();
        $coach = $coach->fresh();

        $this->assertTrue($coach->isCoachNowVerified());
        $this->assertFalse($coach->hasVerifiedBackgroundCheck());
        $this->assertSame('none', $coach->background_check_status);
    }

    private function admin(): User
    {
        $admin = new User;
        $admin->forceFill([
            'name' => 'Ada Admin',
            'email' => 'ada-'.Str::random(5).'@example.test',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->save();

        return $admin;
    }

    /** @param array<string, mixed> $overrides */
    private function adminUpdate(Coach $coach, array $overrides): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())
            ->from('/admin/coaches')
            ->patch(route('admin.coaches.update', $coach), $overrides + [
                'display_name' => $coach->display_name,
                'location_id' => $this->park->id,
                'sport' => User::SPORTS[0],
                'specialty' => Coach::SPECIALTIES[0],
                'rate' => 40,
                'status' => 'active',
                'plan' => array_key_first(Coach::PLANS),
                'approval_status' => 'none',
            ]);
    }

    public function test_an_admin_must_give_the_provider_and_date_to_mark_a_check_clear(): void
    {
        $coach = $this->coach();

        $this->adminUpdate($coach, ['background_check_status' => 'clear'])
            ->assertSessionHasErrors(['background_check_provider', 'background_check_date']);
        $this->assertFalse($coach->fresh()->hasVerifiedBackgroundCheck());

        $this->adminUpdate($coach, [
            'background_check_status' => 'clear',
            'background_check_provider' => 'NCSI',
            'background_check_date' => '2026-11-03',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($coach->fresh()->hasVerifiedBackgroundCheck());
        $this->assertNull($coach->fresh()->verified_at);

        $this->adminUpdate($coach, ['background_check_status' => 'none'])->assertSessionHasNoErrors();
        $this->assertNull($coach->fresh()->background_check_provider);
    }

    public function test_the_admin_verify_button_leaves_the_background_check_alone(): void
    {
        $coach = $this->coach();

        $this->actingAs($this->admin())
            ->patch(route('admin.coaches.approval', $coach), ['action' => 'verify'])
            ->assertRedirect();

        $coach = $coach->fresh();
        $this->assertNotNull($coach->verified_at);
        $this->assertSame('none', $coach->background_check_status);
    }

    public function test_parents_see_the_badge_with_the_provider_and_date(): void
    {
        $coach = $this->coach();
        $coach->forceFill(['verified_at' => now()])->save();

        $this->get(route('coach-profile', $coach))
            ->assertOk()
            ->assertSee('Profile Verified')
            ->assertDontSee('Background check verified')
            ->assertDontSee('CoachNow Verified');

        $coach->forceFill([
            'background_check_status' => 'clear',
            'background_check_provider' => 'NCSI',
            'background_check_date' => '2026-11-03',
        ])->save();

        $this->get(route('coach-profile', $coach->fresh()))
            ->assertSee('Background check verified')
            ->assertSee('NCSI · checked Nov 3, 2026');
    }

    public function test_every_new_email_renders_with_the_details_families_need(): void
    {
        $session = $this->postRequest($parent = $this->member());
        $coach = $this->coach();
        $this->accept($session, $coach);
        $this->join($session, $joiner = $this->member('Jo Joiner'));
        $session = $session->fresh(['hostCoach.user', 'players.user']);

        $accepted = (new SessionRequestAcceptedMail($session))->render();
        $this->assertStringContainsString('$25 per player', $accepted);
        $this->assertStringContainsString('Venmo @casey', $accepted);
        $this->assertStringContainsString($coach->user->email, $accepted);

        $join = (new SessionRequestJoinMail($session, $session->playerFor($joiner)))->render();
        $this->assertStringContainsString($joiner->email, $join);

        $this->assertStringContainsString('Your place is confirmed', (new SessionRequestPlaceMail($session, true))->render());
        $this->assertStringContainsString('wasn', (new SessionRequestPlaceMail($session, false))->render());

        $parent->forceFill(['subscription_ends_at' => '2026-12-10 09:00:00'])->save();
        $membership = (new MembershipConfirmedMail($parent->fresh()))->render();
        $this->assertStringContainsString('$10 per month', $membership);
        $this->assertStringContainsString('Dec 10, 2026', $membership);
        $this->assertStringContainsString('Renews automatically', $membership);
    }

    public function test_stripe_checkout_repeats_the_billing_terms(): void
    {
        $terms = StripeService::membershipCheckoutTerms();

        $this->assertStringContainsString('charged $10 today', $terms);
        $this->assertStringContainsString('renews automatically until you cancel', $terms);
        $this->assertLessThanOrEqual(1200, strlen($terms));
    }
}
