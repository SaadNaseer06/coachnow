<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupSessionAndContactTest extends TestCase
{
    use RefreshDatabase;

    private Location $park;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-13 09:00:00', config('app.timezone')));
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

    private function user(string $role, string $name, ?string $phone = '(951) 555-0100', bool $member = false): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::random(5).'@example.test',
            'password' => 'password',
            'role' => $role,
            'phone' => $phone,
            'email_verified_at' => now(),
            'subscription_status' => $member ? 'active' : null,
            'subscription_ends_at' => $member ? now()->addMonth() : null,
        ])->save();

        return $user;
    }

    private function coach(?string $phone = '(951) 555-0199'): Coach
    {
        $coach = new Coach;
        $coach->forceFill([
            'user_id' => $this->user('coach', 'Casey Coach', $phone)->id,
            'location_id' => $this->park->id,
            'display_name' => 'Casey Coach',
            'slug' => 'casey-'.Str::random(5),
            'status' => 'active',
            'plan' => 'founding',
            'rate' => 40,
        ])->save();

        return $coach;
    }

    /** A group request accepted and confirmed, as Lee's session was. */
    private function confirmedRequestSession(User $parent, Coach $coach): Booking
    {
        $reference = $this->actingAs($parent)->postJson('/api/session-requests', [
            'location_id' => 'test-park',
            'location_name' => 'Test Park',
            'session_date' => '2026-11-20',
            'session_time' => '5:00 PM',
            'session_type' => 'Skills Training — Group Session',
            'sport' => User::SPORTS[0],
            'max_players' => 4,
        ])->assertCreated()->json('data.id');

        $this->actingAs($coach->user)->postJson("/api/session-requests/{$reference}/accept", ['price_per_player' => 25])->assertOk();
        $session = SessionRequest::query()->where('reference', $reference)->sole();
        $player = $session->players()->sole();
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$reference}/players/{$player->id}/confirm")->assertOk();

        return Booking::query()->where('session_request_id', $session->id)->sole();
    }

    public function test_opening_a_requested_group_to_the_public_does_not_clash_with_itself(): void
    {
        $parent = $this->user('athlete', 'Pat Parent', '(951) 555-0100', true);
        $coach = $this->coach();
        $booking = $this->confirmedRequestSession($parent, $coach);

        // What the schedule's session dialog sends when "Open to public" is ticked.
        $this->actingAs($coach->user)->patchJson("/coach/schedule/sessions/{$booking->id}", [
            'athlete_id' => $parent->id,
            'session_date' => '2026-11-20',
            'session_time' => '17:00',
            'duration_minutes' => 60,
            'location_id' => $this->park->id,
            'session_type' => 'Skills Training — Group Session',
            'max_players' => 4,
            'age_min' => 8,
            'age_max' => 12,
            'price_per_player' => 25,
            'is_public' => true,
        ])->assertOk();

        $this->assertTrue((bool) $booking->fresh()->groupSession->is_public);
    }

    public function test_a_real_clash_with_another_session_is_still_caught(): void
    {
        $parent = $this->user('athlete', 'Pat Parent', '(951) 555-0100', true);
        $coach = $this->coach();
        $booking = $this->confirmedRequestSession($parent, $coach);

        // The same player already has a different session at 6 PM.
        $other = $booking->replicate(['reference', 'group_session_id', 'session_request_id']);
        $other->forceFill(['reference' => 'CN-OTHER', 'session_time' => '18:00:00', 'status' => 'confirmed'])->save();

        $this->actingAs($coach->user)->patchJson("/coach/schedule/sessions/{$booking->id}", [
            'athlete_id' => $parent->id,
            'session_date' => '2026-11-20',
            'session_time' => '18:00',
            'location_id' => $this->park->id,
            'session_type' => 'Skills Training — Group Session',
        ])->assertUnprocessable();
    }

    /** @param array<string, mixed> $overrides */
    private function register(array $overrides): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', $overrides + [
            'name' => 'New Person',
            'email' => 'new-'.Str::random(6).'@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'athlete',
            'sport' => User::SPORTS[0],
        ]);
    }

    public function test_parents_must_give_a_phone_number_to_sign_up(): void
    {
        $this->register([])->assertSessionHasErrors('phone');
        $this->register(['phone' => '555-01'])->assertSessionHasErrors('phone');

        $this->register(['email' => 'parent@example.test', 'phone' => '(951) 555-0142'])->assertSessionHasNoErrors();
        $this->assertSame('(951) 555-0142', User::query()->where('email', 'parent@example.test')->value('phone'));
    }

    public function test_coaches_must_give_a_phone_number_to_sign_up(): void
    {
        $coach = [
            'role' => 'coach',
            'specialty' => Coach::SPECIALTIES[0],
            'experience' => Coach::EXPERIENCE_OPTIONS[0],
            'bio' => 'I coach.',
        ];

        $this->register($coach)->assertSessionHasErrors('phone');
        $this->register($coach + ['email' => 'coach@example.test', 'phone' => '951-555-0177'])->assertSessionHasNoErrors();
        $this->assertSame('951-555-0177', User::query()->where('email', 'coach@example.test')->value('phone'));
    }

    public function test_the_signup_page_asks_everyone_for_a_phone_number(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertDontSee('Phone <span class="text-zinc-400 font-normal">(optional)</span>', false)
            ->assertSee('parent or guardian', false);
    }

    public function test_admins_must_give_a_phone_number_when_adding_a_coach(): void
    {
        $admin = $this->user('admin', 'Ada Admin');
        $coach = [
            'name' => 'Coach New',
            'email' => 'coach-new@example.test',
            'password' => 'password123',
            'location_id' => $this->park->id,
            'sport' => User::SPORTS[0],
            'specialty' => Coach::SPECIALTIES[0],
            'rate' => 40,
            'status' => 'active',
        ];

        $this->actingAs($admin)->post('/admin/coaches', $coach)->assertSessionHasErrors('phone');
        $this->actingAs($admin)->post('/admin/coaches', $coach + ['phone' => '(951) 555-0150'])->assertSessionHasNoErrors();
        $this->assertSame('(951) 555-0150', User::query()->where('email', 'coach-new@example.test')->value('phone'));
    }

    public function test_a_coach_without_a_phone_is_asked_for_one_when_accepting(): void
    {
        $parent = $this->user('athlete', 'Pat Parent', '(951) 555-0100', true);
        $coach = $this->coach(null);
        $reference = $this->actingAs($parent)->postJson('/api/session-requests', [
            'location_name' => 'Test Park',
            'session_date' => '2026-11-20',
            'session_time' => '5:00 PM',
            'session_type' => 'Skills Training — Group Session',
            'sport' => User::SPORTS[0],
        ])->assertCreated()->json('data.id');

        $this->actingAs($coach->user)->postJson("/api/session-requests/{$reference}/accept", ['price_per_player' => 25])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->actingAs($coach->user)->postJson("/api/session-requests/{$reference}/accept", ['price_per_player' => 25, 'phone' => '(951) 555-0123'])
            ->assertOk()
            ->assertJsonPath('data.status', 'awaiting_deposit');
        $this->assertSame('(951) 555-0123', $coach->user->fresh()->phone);
    }

    public function test_coaches_can_keep_their_phone_up_to_date_on_their_profile(): void
    {
        $coach = $this->coach(null);

        $this->actingAs($coach->user)->get('/coach/profile')->assertOk()->assertSee('name="phone"', false);

        $profile = [
            'display_name' => 'Casey Coach',
            'sports' => [User::SPORTS[0]],
            'specialties' => [Coach::SPECIALTIES[0]],
            'experience' => Coach::EXPERIENCE_OPTIONS[0],
            'ages' => 'Ages 8-12',
            'location_id' => $this->park->id,
            'rate' => 40,
        ];

        $this->actingAs($coach->user)->put('/coach/profile', $profile)->assertSessionHasErrors('phone');
        $this->actingAs($coach->user)->put('/coach/profile', $profile + ['phone' => '(951) 555-0188'])->assertSessionHasNoErrors();
        $this->assertSame('(951) 555-0188', $coach->user->fresh()->phone);
    }

    public function test_an_admin_can_remove_a_test_account_and_everything_it_made(): void
    {
        $admin = $this->user('admin', 'Ada Admin');
        $qa = $this->user('athlete', 'QA Family', '(951) 555-0100', true);
        $other = $this->user('athlete', 'Real Family', '(951) 555-0101', true);
        $coach = $this->coach();
        $booking = $this->confirmedRequestSession($qa, $coach);
        $session = $booking->sessionRequest;

        // A real family also joined, and must keep their place.
        $this->actingAs($other)->postJson("/api/session-requests/{$session->reference}/join")->assertOk();
        $joiner = $session->players()->where('user_id', $other->id)->sole();
        $this->actingAs($coach->user)->postJson("/api/session-requests/{$session->reference}/players/{$joiner->id}/confirm")->assertOk();

        $this->actingAs($admin)->get('/admin/athletes')->assertOk()->assertSee('Remove');
        $this->actingAs($admin)->delete("/admin/athletes/{$qa->id}")
            ->assertRedirect('/admin/athletes')
            ->assertSessionHas('success');

        $this->assertNull(User::query()->find($qa->id));
        $this->assertNull(SessionRequest::query()->find($session->id));
        $this->assertSame(0, Booking::query()->where('athlete_id', $qa->id)->count());
        $this->assertNotNull(User::query()->find($other->id));
    }

    public function test_remove_refuses_coaches_renewing_memberships_and_non_admins(): void
    {
        $admin = $this->user('admin', 'Ada Admin');
        $coach = $this->coach();
        $this->actingAs($admin)->delete("/admin/athletes/{$coach->user->id}")->assertNotFound();

        $paying = $this->user('athlete', 'Paying Family', '(951) 555-0100', true);
        $paying->forceFill(['stripe_subscription_id' => 'sub_live', 'subscription_cancel_at_period_end' => false])->save();
        $this->actingAs($admin)->from('/admin/athletes')->delete("/admin/athletes/{$paying->id}")->assertSessionHas('error');
        $this->assertNotNull(User::query()->find($paying->id));

        $parent = $this->user('athlete', 'Pat Parent');
        // The admin area turns non-admins away; the account must survive either way.
        $this->actingAs($parent)->delete("/admin/athletes/{$paying->id}")->assertRedirect();
        $this->assertNotNull(User::query()->find($paying->id));
    }
}
