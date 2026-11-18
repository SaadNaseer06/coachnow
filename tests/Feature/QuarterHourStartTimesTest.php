<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\User;
use App\Services\CoachAvailabilityService;
use App\Services\SessionBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuarterHourStartTimesTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2026-11-23';

    private Location $park;

    private Coach $coach;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-22 09:00:00', config('app.timezone')));
        config(['coachnow.subscription.gate_features' => true, 'coachnow.payments.sample_mode' => true]);

        $this->park = Location::query()->create(['name' => 'Sommers Bend', 'slug' => 'sommers-bend', 'area' => 'Temecula, CA', 'distance_miles' => 1, 'status' => 'live']);

        $user = new User;
        $user->forceFill(['name' => 'Casey Coach', 'email' => 'casey@example.test', 'password' => 'password', 'role' => 'coach', 'phone' => '(951) 555-0199', 'email_verified_at' => now()])->save();
        $this->coach = new Coach;
        $this->coach->forceFill(['user_id' => $user->id, 'location_id' => $this->park->id, 'display_name' => 'Casey Coach', 'slug' => 'casey', 'status' => 'active', 'plan' => 'founding', 'rate' => 40])->save();

        // Mondays, 4–7 PM, one-hour sessions.
        $this->coach->availabilitySlots()->create([
            'location_id' => $this->park->id, 'day_of_week' => 1, 'start_time' => '16:00', 'end_time' => '19:00', 'duration_minutes' => 60,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return list<string> start times offered as 1-on-1 on Monday */
    private function oneOnOneTimes(): array
    {
        $slots = app(CoachAvailabilityService::class)->openSlotsForCoach($this->coach->fresh(), Carbon::parse(self::MONDAY), Carbon::parse(self::MONDAY));

        return array_values(array_map(fn ($s) => $s['time'], array_filter($slots, fn ($s) => empty($s['group_session_id']))));
    }

    private function parent(): User
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Pat Parent', 'email' => 'pat-'.Str::random(5).'@example.test', 'password' => 'password', 'role' => 'athlete',
            'phone' => '(951) 555-0100', 'email_verified_at' => now(), 'subscription_status' => 'active', 'subscription_ends_at' => now()->addMonth(),
        ])->save();

        return $user;
    }

    public function test_one_on_one_start_times_come_every_15_minutes(): void
    {
        $this->assertSame(
            ['16:00', '16:15', '16:30', '16:45', '17:00', '17:15', '17:30', '17:45', '18:00'],
            $this->oneOnOneTimes(),
            'A one-hour session must still end by 7 PM, so 6:00 is the last start.'
        );
    }

    public function test_a_booked_session_blocks_every_start_time_that_would_overlap_it(): void
    {
        Booking::query()->create([
            'reference' => 'CN-Q1', 'coach_id' => $this->coach->id, 'location_id' => $this->park->id, 'athlete_name' => 'Booked Player',
            'session_type' => 'Private 1-on-1', 'session_date' => self::MONDAY, 'session_time' => '17:00:00', 'duration_minutes' => 60,
            'amount' => 40, 'status' => 'confirmed', 'payment_status' => 'paid',
        ]);

        $this->assertSame(['16:00', '18:00'], $this->oneOnOneTimes());
    }

    public function test_an_open_group_keeps_its_slot_and_blocks_one_on_one_times_that_run_into_it(): void
    {
        CoachGroupSession::query()->create([
            'coach_id' => $this->coach->id, 'location_id' => $this->park->id, 'session_type' => 'Small Group',
            'session_date' => self::MONDAY, 'session_time' => '17:00', 'duration_minutes' => 60, 'max_players' => 6,
            'status' => 'open', 'is_public' => true, 'age_range' => 'Ages 8-12', 'price_per_player' => 25,
        ]);

        $slots = app(CoachAvailabilityService::class)->openSlotsForCoach($this->coach->fresh(), Carbon::parse(self::MONDAY), Carbon::parse(self::MONDAY));

        $this->assertSame(['16:00', '18:00'], $this->oneOnOneTimes());
        $group = array_values(array_filter($slots, fn ($s) => ! empty($s['group_session_id'])));
        $this->assertCount(1, $group);
        $this->assertSame('17:00', $group[0]['time']);
        $this->assertTrue((bool) $group[0]['joinable']);
    }

    public function test_the_coach_buffer_still_applies(): void
    {
        $this->coach->forceFill(['buffer_minutes' => 15])->save();
        Booking::query()->create([
            'reference' => 'CN-Q2', 'coach_id' => $this->coach->id, 'location_id' => $this->park->id, 'athlete_name' => 'Booked Player',
            'session_type' => 'Private 1-on-1', 'session_date' => self::MONDAY, 'session_time' => '16:00:00', 'duration_minutes' => 60,
            'amount' => 40, 'status' => 'confirmed', 'payment_status' => 'paid',
        ]);

        // 4–5 PM booked plus a 15-minute gap, so the next start is 5:15; 6:00 still ends by 7.
        $this->assertSame(['17:15', '17:30', '17:45', '18:00'], $this->oneOnOneTimes());
    }

    public function test_a_parent_can_book_a_quarter_past_start_but_not_two_overlapping_times(): void
    {
        $bookings = app(SessionBookingService::class);
        $parent = $this->parent();
        $slot = fn (string $time) => ['date' => self::MONDAY, 'time' => $time, 'location_id' => $this->park->id];

        try {
            $bookings->createMultipleDirectBookings($this->coach, $parent, [$slot('16:00'), $slot('16:15')]);
            $this->fail('Overlapping picks should be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('overlap', collect($e->errors())->flatten()->first());
        }

        $created = $bookings->createMultipleDirectBookings($this->coach, $parent, [$slot('16:15')]);
        $this->assertSame('16:15', substr((string) $created[0]->session_time, 0, 5));
        $this->assertNotContains('16:30', $this->oneOnOneTimes());
        $this->assertContains('17:15', $this->oneOnOneTimes());
    }

    public function test_request_a_session_accepts_quarter_hour_evening_times(): void
    {
        $parent = $this->parent();

        $reference = $this->actingAs($parent)->postJson('/api/session-requests', [
            'location_id' => 'sommers-bend', 'location_name' => 'Sommers Bend', 'session_date' => self::MONDAY,
            'session_time' => '7:15 PM', 'session_type' => 'Skills Training — Group Session', 'sport' => User::SPORTS[0],
        ])->assertCreated()->json('data.id');

        $this->assertSame('19:15:00', SessionRequest::query()->where('reference', $reference)->value('session_time'));
    }
}
