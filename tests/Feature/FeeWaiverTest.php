<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\Location;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lee's launch offer: no $2 booking fee on bookings made through February 28; it comes back
 * for non-members on March 1, and members never pay it.
 */
class FeeWaiverTest extends TestCase
{
    use RefreshDatabase;

    private Location $park;

    private Coach $coach;

    private FakeStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['coachnow.subscription.gate_features' => true, 'coachnow.payments.sample_mode' => false, 'coachnow.payments.convenience_fee' => 2, 'coachnow.payments.fee_waived_until' => '2027-02-28']);
        $this->stripe = new FakeStripe;
        $this->app->instance(StripeService::class, $this->stripe);

        $this->park = Location::query()->create(['name' => 'Sommers Bend', 'slug' => 'sommers-bend', 'area' => 'Temecula, CA', 'distance_miles' => 1, 'status' => 'live']);
        $user = new User;
        $user->forceFill(['name' => 'Casey Coach', 'email' => 'casey@example.test', 'password' => 'password', 'role' => 'coach', 'phone' => '(951) 555-0199', 'email_verified_at' => now()])->save();
        $this->coach = new Coach;
        $this->coach->forceFill(['user_id' => $user->id, 'location_id' => $this->park->id, 'display_name' => 'Casey Coach', 'slug' => 'casey', 'status' => 'active', 'plan' => 'founding', 'rate' => 40])->save();
        // Every day, so any test date has open times.
        foreach (range(0, 6) as $day) {
            $this->coach->availabilitySlots()->create(['location_id' => $this->park->id, 'day_of_week' => $day, 'start_time' => '16:00', 'end_time' => '19:00', 'duration_minutes' => 60]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function at(string $when): void
    {
        Carbon::setTestNow(Carbon::parse($when, config('app.timezone')));
    }

    private function family(bool $member = false): User
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Pat Parent', 'email' => 'pat-'.Str::random(5).'@example.test', 'password' => 'password', 'role' => 'athlete', 'phone' => '(951) 555-0100', 'email_verified_at' => now(),
            'subscription_status' => $member ? 'active' : null, 'subscription_ends_at' => $member ? now()->addYear() : null,
        ])->save();

        return $user;
    }

    private function slot(string $date): array
    {
        return ['coach' => 'casey', 'date' => $date, 'time' => '16:00', 'location_id' => $this->park->id, 'duration_minutes' => 60];
    }

    public function test_during_the_offer_nobody_pays_and_checkout_says_so(): void
    {
        $this->at('2026-12-05 10:00');
        $parent = $this->family();

        $this->actingAs($parent)->get('/book/casey')->assertOk()
            ->assertSee('$2 booking fee waived through February 28')
            ->assertSee('data-convenience-fee="0"', false);

        $this->postJson('/api/stripe/booking-intent', $this->slot('2026-12-08'))->assertOk()->assertJsonPath('data.requires_payment', false);
        $this->postJson('/api/bookings', $this->slot('2026-12-08'))->assertCreated()->assertJsonPath('data.convenience_fee', 0)->assertJsonPath('data.due_to_coach', 40);

        $this->assertSame([], $this->stripe->intents, 'No card step during the offer.');
        $booking = Booking::query()->sole();
        $this->assertSame('offer', $booking->fee_status);
        $this->assertEquals(0, $booking->platform_fee_amount);
        $this->assertEquals(40, $booking->amount, 'Parents still pay the coach the session price.');
        Mail::assertSent(BookingConfirmedMail::class, fn ($m) => str_contains($m->render(), '$2 booking fee waived through February 28'));
    }

    public function test_the_last_minute_of_february_28_is_still_free(): void
    {
        $this->at('2027-02-28 23:59');
        $this->actingAs($this->family())->postJson('/api/bookings', $this->slot('2027-03-02'))->assertCreated();
        $this->assertSame([], $this->stripe->intents);
    }

    public function test_the_fee_is_back_for_non_members_on_march_1(): void
    {
        $this->at('2027-03-01 00:00');
        $parent = $this->family();

        $this->actingAs($parent)->get('/book/casey')->assertOk()->assertDontSee('waived through')->assertSee('data-convenience-fee="2"', false);
        $this->postJson('/api/bookings', $this->slot('2027-03-03'))->assertStatus(422)->assertJsonPath('requires_stripe', true);
        $this->postJson('/api/stripe/booking-intent', $this->slot('2027-03-03'))->assertOk()->assertJsonPath('data.amount', 2);
    }

    public function test_members_never_pay_before_or_after(): void
    {
        foreach (['2026-12-05 10:00', '2027-03-05 10:00'] as $i => $when) {
            $this->at($when);
            $member = $this->family(member: true);
            $this->actingAs($member)->get('/book/casey')->assertOk()->assertDontSee('waived through');
            $this->postJson('/api/bookings', $this->slot(Carbon::parse($when)->addDays(2)->toDateString()))->assertCreated();
            $this->assertSame('waived', Booking::query()->latest('id')->first()->fee_status, $when);
        }
        $this->assertSame([], $this->stripe->intents);
    }

    public function test_the_offer_can_be_switched_off_or_moved_in_settings(): void
    {
        $this->at('2026-12-05 10:00');
        config(['coachnow.payments.fee_waived_until' => null]);
        $this->assertSame(2.0, User::convenienceFeeFor($this->family()));

        config(['coachnow.payments.fee_waived_until' => '2027-03-31']);
        $this->assertSame('$2 booking fee waived through March 31', User::feeWaiverLine());
    }

    public function test_other_pages_mention_it(): void
    {
        $this->at('2026-12-05 10:00');
        $this->get('/faq')->assertOk()->assertSee('Launch offer: $2 booking fee waived through February 28.');
        $this->actingAs($this->family())->get('/player-dashboard')->assertOk()->assertSee('currently waived through February 28');
    }
}
