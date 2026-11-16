<?php

namespace Tests\Feature;

use App\Mail\SessionRescheduledMail;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\User;
use App\Services\NominatimGeocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class JoinedPlayersAndCoachLocationsTest extends TestCase
{
    use RefreshDatabase;

    private Location $park;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:00:00', config('app.timezone')));
        config(['coachnow.subscription.gate_features' => true, 'coachnow.payments.sample_mode' => true]);
        // Never call the real map service from a test.
        $this->mock(NominatimGeocoder::class, fn ($mock) => $mock->shouldReceive('geocode')->andReturn(['lat' => 33.49, 'lng' => -117.14, 'label' => 'test']));

        $this->park = Location::query()->create([
            'name' => 'Sommers Bend', 'slug' => 'sommers-bend', 'area' => 'Temecula, CA', 'distance_miles' => 1, 'status' => 'live',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function parent(string $name): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::random(5).'@example.test',
            'password' => 'password',
            'role' => 'athlete',
            'phone' => '(951) 555-0100',
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
            'name' => $name, 'email' => Str::slug($name).'-'.Str::random(5).'@example.test', 'password' => 'password',
            'role' => 'coach', 'phone' => '(951) 555-0199', 'email_verified_at' => now(),
        ])->save();

        $coach = new Coach;
        $coach->forceFill([
            'user_id' => $user->id, 'location_id' => $this->park->id, 'display_name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5), 'status' => 'active', 'plan' => 'founding', 'rate' => 40,
        ])->save();

        return $coach;
    }

    private function request(User $parent, array $overrides = []): SessionRequest
    {
        $reference = $this->actingAs($parent)->postJson('/api/session-requests', $overrides + [
            'location_id' => 'sommers-bend', 'location_name' => 'Sommers Bend',
            'session_date' => '2026-11-24', 'session_time' => '5:00 PM',
            'session_type' => 'Skills Training — Group Session', 'sport' => User::SPORTS[0],
            'price_range' => 'Up to $25 / player', 'min_players' => 3, 'max_players' => 6,
        ])->assertCreated()->json('data.id');

        return SessionRequest::query()->where('reference', $reference)->sole();
    }

    // ---------- Joined players before a coach accepts ----------

    public function test_coaches_see_everyone_who_has_joined_before_accepting(): void
    {
        $arianna = $this->parent('Arianna Parent');
        $session = $this->request($arianna);
        $this->actingAs($this->parent('Beckam Parent'))->postJson("/api/session-requests/{$session->reference}/join")->assertOk();
        $this->actingAs($this->parent('Third Parent'))->postJson("/api/session-requests/{$session->reference}/join")->assertOk();
        $coach = $this->coach();

        $card = $this->actingAs($coach->user)->getJson('/api/session-requests')->assertOk()->json('data.0');
        $this->assertSame('open', $card['status']);
        $this->assertSame(3, $card['joined_count']);
        $this->assertSame(6, $card['max_players']);
        $this->assertEqualsCanonicalizing(['Arianna Parent', 'Beckam Parent', 'Third Parent'], array_column($card['players'], 'name'));
        $this->assertArrayNotHasKey('email', $card['players'][0], 'Contact details stay hidden until the coach accepts.');

        $this->actingAs($coach->user)->get('/coach/dashboard')
            ->assertOk()
            ->assertSee('Currently joined')
            ->assertSee('3 players')
            ->assertSee('Maximum: 6')
            ->assertSee('View players');
    }

    public function test_the_count_drops_when_a_family_leaves_and_they_can_come_back(): void
    {
        $session = $this->request($this->parent('Arianna Parent'));
        $beckam = $this->parent('Beckam Parent');
        $this->actingAs($beckam)->postJson("/api/session-requests/{$session->reference}/join")->assertOk();
        $coach = $this->coach();

        $this->actingAs($beckam)->get('/player-dashboard')->assertSee('data-leave-request', false);
        $this->actingAs($beckam)->postJson("/api/session-requests/{$session->reference}/leave")
            ->assertOk()
            ->assertJsonPath('message', 'You’ve left this session.');
        $this->assertSame(1, $this->actingAs($coach->user)->getJson('/api/session-requests')->json('data.0.joined_count'));

        $this->actingAs($beckam)->postJson("/api/session-requests/{$session->reference}/join")->assertOk();
        $this->assertSame(2, $this->actingAs($coach->user)->getJson('/api/session-requests')->json('data.0.joined_count'));
    }

    public function test_the_requester_and_confirmed_families_cannot_just_leave(): void
    {
        $arianna = $this->parent('Arianna Parent');
        $session = $this->request($arianna);
        $beckam = $this->parent('Beckam Parent');
        $this->actingAs($beckam)->postJson("/api/session-requests/{$session->reference}/join")->assertOk();

        $this->actingAs($arianna)->postJson("/api/session-requests/{$session->reference}/leave")
            ->assertUnprocessable()->assertJsonPath('message', 'This is your own request. Cancel it instead.');

        $coach = $this->coach();
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/accept", ['price_per_player' => 25])->assertOk();
        $player = $session->players()->where('user_id', $beckam->id)->sole();
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/players/{$player->id}/confirm")->assertOk();

        $this->actingAs($beckam)->postJson("/api/session-requests/{$session->reference}/leave")
            ->assertUnprocessable()->assertJsonPath('message', 'Your place is confirmed. Contact your coach to cancel it.');
    }

    // ---------- Coaches entering their own location ----------

    private function newSession(Coach $coach, array $location): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($coach->user)->postJson('/coach/schedule/sessions', $location + [
            'player_name' => 'Walk-in Player', 'session_date' => '2026-11-24', 'session_time' => '17:00',
            'session_type' => 'Private 1-on-1', 'duration_minutes' => 60,
        ]);
    }

    public function test_a_coach_can_add_a_session_at_their_own_location(): void
    {
        $coach = $this->coach();
        $venue = ['location_id' => 'other', 'new_location_name' => 'Pauba Ranch Field', 'new_location_address' => '31155 Pauba Rd, Temecula, CA 92592'];

        $this->newSession($coach, $venue)->assertOk();

        $location = Location::query()->where('name', 'Pauba Ranch Field')->sole();
        $this->assertSame($coach->id, $location->coach_id);
        $this->assertSame('coach', $location->status);
        $this->assertSame('31155 Pauba Rd, Temecula, CA 92592', $location->area);
        $this->assertSame($location->id, Booking::query()->where('coach_id', $coach->id)->sole()->location_id);

        // Entering the same venue again reuses it instead of making a copy.
        $this->newSession($coach, $venue + ['session_time' => '18:30'])->assertOk();
        $this->assertSame(1, Location::query()->where('name', 'Pauba Ranch Field')->count());

        $this->actingAs($coach->user)->get('/coach/schedule')->assertSee('Pauba Ranch Field')->assertSee('Other location…');
    }

    public function test_a_custom_location_needs_a_real_address(): void
    {
        $coach = $this->coach();

        $this->newSession($coach, ['location_id' => 'other', 'new_location_name' => 'My Field'])
            ->assertUnprocessable()->assertJsonValidationErrors('new_location_address');
        $this->newSession($coach, ['location_id' => 'other', 'new_location_name' => 'My Field', 'new_location_address' => 'Behind the school'])
            ->assertUnprocessable()->assertJsonValidationErrors('new_location_address');
        $this->assertSame(0, Location::query()->where('status', 'coach')->count());
    }

    public function test_a_coachs_own_location_is_private_to_them(): void
    {
        $owner = $this->coach('Owner Coach');
        $this->newSession($owner, ['location_id' => 'other', 'new_location_name' => 'Secret Field', 'new_location_address' => '123 Main St, Murrieta, CA'])->assertOk();
        $secret = Location::query()->where('name', 'Secret Field')->sole();

        $this->newSession($this->coach('Other Coach'), ['location_id' => $secret->id])
            ->assertUnprocessable()->assertJsonValidationErrors('location_id');

        // Public park lists only show admin parks.
        $this->get('/')->assertDontSee('Secret Field');
        $this->actingAs($this->parent('Any Parent'))->get('/request-session')->assertDontSee('Secret Field')->assertSee('Sommers Bend');
    }

    public function test_working_hours_can_use_a_custom_location(): void
    {
        $coach = $this->coach();

        $this->actingAs($coach->user)->post('/coach/schedule/availability', [
            'location_id' => 'other', 'new_location_name' => 'Pauba Ranch Field', 'new_location_address' => '31155 Pauba Rd, Temecula, CA 92592',
            'days' => [1, 3], 'start_time' => '16:00', 'end_time' => '19:00',
        ])->assertSessionHasNoErrors();

        $venue = Location::query()->where('name', 'Pauba Ranch Field')->sole();
        $this->assertSame(2, $coach->availabilitySlots()->where('location_id', $venue->id)->count());
    }

    public function test_changing_a_booked_sessions_location_updates_it_and_tells_the_family(): void
    {
        $parent = $this->parent('Arianna Parent');
        $coach = $this->coach();
        $session = $this->request($parent);
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/accept", ['price_per_player' => 25])->assertOk();
        $player = $session->players()->sole();
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/players/{$player->id}/confirm")->assertOk();
        $booking = Booking::query()->where('session_request_id', $session->id)->sole();

        $this->actingAs($coach->user)->patchJson("/coach/schedule/sessions/{$booking->id}", [
            'location_id' => 'other',
            'new_location_name' => 'Pauba Ranch Field',
            'new_location_address' => '31155 Pauba Rd, Temecula, CA 92592',
        ])->assertOk()->assertJsonPath('data.notified', 1);

        $venue = Location::query()->where('name', 'Pauba Ranch Field')->sole();
        $this->assertSame($venue->id, $booking->fresh()->location_id);
        $this->assertSame('Pauba Ranch Field', $session->fresh()->location_name);

        Mail::assertSent(SessionRescheduledMail::class, fn ($mail) => $mail->hasTo($parent->email) && $mail->oldLocation?->name === 'Sommers Bend');

        $this->actingAs($parent)->get('/player-dashboard')
            ->assertSee('Pauba Ranch Field · 31155 Pauba Rd, Temecula, CA 92592')
            ->assertSee('google.com/maps/search', false);
    }

    public function test_saving_without_changes_sends_nothing_but_a_new_time_does(): void
    {
        $parent = $this->parent('Arianna Parent');
        $coach = $this->coach();
        $session = $this->request($parent);
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/accept", ['price_per_player' => 25])->assertOk();
        $player = $session->players()->sole();
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/players/{$player->id}/confirm")->assertOk();
        $booking = Booking::query()->where('session_request_id', $session->id)->sole();

        $this->actingAs($coach->user)->patchJson("/coach/schedule/sessions/{$booking->id}", ['notes' => 'Bring water'])
            ->assertOk()->assertJsonPath('data.notified', 0);
        Mail::assertNotSent(SessionRescheduledMail::class);

        $this->actingAs($coach->user)->patchJson("/coach/schedule/sessions/{$booking->id}", ['session_time' => '18:00'])
            ->assertOk()->assertJsonPath('data.notified', 1);
        Mail::assertSent(SessionRescheduledMail::class, fn ($mail) => $mail->oldLocation === null);
    }

    public function test_the_change_email_shows_the_new_address_and_directions(): void
    {
        $coach = $this->coach();
        $old = $this->park;
        $new = Location::query()->create(['name' => 'Pauba Ranch Field', 'slug' => 'pauba', 'area' => '31155 Pauba Rd, Temecula, CA 92592', 'status' => 'coach', 'coach_id' => $coach->id]);
        $booking = Booking::query()->create([
            'reference' => 'CN-MAIL1', 'coach_id' => $coach->id, 'location_id' => $new->id, 'athlete_name' => 'Arianna',
            'session_type' => 'Private 1-on-1', 'session_date' => '2026-11-24', 'session_time' => '17:00:00',
            'duration_minutes' => 60, 'amount' => 25, 'status' => 'confirmed', 'payment_status' => 'direct',
        ]);

        $html = (new SessionRescheduledMail($booking->load(['coach', 'location']), ['Arianna'], '2026-11-24', '17:00', $old))->render();

        $this->assertStringContainsString('Your session location changed', $html);
        $this->assertStringContainsString('Sommers Bend · Temecula, CA', $html);
        $this->assertStringContainsString('Pauba Ranch Field · 31155 Pauba Rd, Temecula, CA 92592', $html);
        $this->assertStringContainsString('google.com/maps/search', $html);
        $this->assertSame('Session location changed — now at Pauba Ranch Field', (new SessionRescheduledMail($booking, ['Arianna'], '2026-11-24', '17:00', $old))->envelope()->subject);
    }
}
