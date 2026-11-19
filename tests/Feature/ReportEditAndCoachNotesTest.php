<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\CoachPlayerNote;
use App\Models\Location;
use App\Models\SessionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportEditAndCoachNotesTest extends TestCase
{
    use RefreshDatabase;

    private Location $park;

    private Coach $coach;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-20 09:00:00', config('app.timezone')));
        config(['coachnow.subscription.gate_features' => true, 'coachnow.payments.sample_mode' => true]);

        $this->park = Location::query()->create(['name' => 'Sommers Bend', 'slug' => 'sommers-bend', 'area' => 'Temecula, CA', 'distance_miles' => 1, 'status' => 'live']);
        $this->coach = $this->coach('Casey Coach');

        $this->parent = new User;
        $this->parent->forceFill([
            'name' => 'Riley Player', 'email' => 'riley-'.Str::random(5).'@example.test', 'password' => 'password', 'role' => 'athlete',
            'phone' => '(951) 555-0100', 'email_verified_at' => now(), 'subscription_status' => 'active', 'subscription_ends_at' => now()->addMonth(),
        ])->save();

        // A past session puts Riley on Casey's roster.
        Booking::query()->create([
            'reference' => 'CN-R1', 'coach_id' => $this->coach->id, 'athlete_id' => $this->parent->id, 'location_id' => $this->park->id,
            'athlete_name' => 'Riley Player', 'session_type' => 'Private 1-on-1', 'session_date' => '2026-11-18', 'session_time' => '16:00:00',
            'duration_minutes' => 60, 'amount' => 40, 'status' => 'confirmed', 'payment_status' => 'paid',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function coach(string $name): Coach
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

    private function asCoach(?Coach $coach = null): static
    {
        return $this->actingAs(($coach ?? $this->coach)->user);
    }

    private function playerSlug(): string
    {
        return (string) $this->parent->id;
    }

    private function saveReport(bool $share = false): SessionReport
    {
        $this->asCoach()->post('/coach/add-report', [
            'player' => $this->playerSlug(),
            'keywords' => 'first touch, scanning',
            'skill_statuses' => json_encode([['skill' => 'first touch', 'status' => 'developing'], ['skill' => 'scanning', 'status' => 'developing']]),
            'coach_notes_wins' => 'Good first touch',
            'coach_notes_work_ons' => 'Scan earlier',
            'focus' => 'Check your shoulder before the ball arrives.',
            'went_well' => 'Clean first touch under light pressure.',
            'needs_work' => 'Scanning before receiving.',
            'home_plan' => 'Wall passes, 3 x 5 minutes.',
            'summary' => 'Solid session.',
            'recommended_videos' => '[]',
            'share' => $share ? '1' : '0',
        ])->assertRedirect();

        return SessionReport::query()->latest('id')->firstOrFail();
    }

    private function editPayload(array $overrides = []): array
    {
        return array_merge([
            'keywords' => 'first touch, scanning',
            'skill_statuses' => json_encode([['skill' => 'first touch', 'status' => 'strong'], ['skill' => 'scanning', 'status' => 'needs_work']]),
            'coach_notes_wins' => 'Good first touch',
            'coach_notes_work_ons' => 'Scan earlier',
            'focus' => 'Check your shoulder before the ball arrives.',
            'went_well' => 'Clean first touch, even under real pressure.',
            'needs_work' => 'Scanning before receiving.',
            'home_plan' => 'Wall passes, 3 x 5 minutes.',
            'summary' => 'Solid session.',
            'recommended_videos' => '[]',
            'share' => '0',
        ], $overrides);
    }

    public function test_each_saved_report_has_an_edit_button_that_opens_it_filled_in(): void
    {
        $report = $this->saveReport();

        $this->asCoach()->get('/coach/players/'.$this->playerSlug().'?tab=goals')
            ->assertOk()
            ->assertSee(route('coach.reports.edit', $report), false)
            ->assertSee('Skill ratings:')
            ->assertSee('first touch – Developing');

        $this->asCoach()->get(route('coach.reports.edit', $report))
            ->assertOk()
            ->assertSee('Edit Session Report')
            ->assertSee('Clean first touch under light pressure.')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee(e(json_encode($report->skill_statuses)), false)
            ->assertSee('Save changes');
    }

    public function test_the_coach_can_update_feedback_and_fill_in_missed_ratings(): void
    {
        $report = $this->saveReport();

        $this->asCoach()->put(route('coach.reports.update', $report), $this->editPayload())
            ->assertRedirect(route('coach.players.show', ['player' => $this->playerSlug(), 'tab' => 'goals']))
            ->assertSessionHas('status', 'Report updated.');

        $report->refresh();
        $this->assertSame('Clean first touch, even under real pressure.', $report->went_well);
        $this->assertSame([['skill' => 'first touch', 'status' => 'strong'], ['skill' => 'scanning', 'status' => 'needs_work']], $report->skill_statuses);
        $this->assertFalse($report->shared_with_player, 'Editing a private report must not share it.');
        $this->assertSame(1, SessionReport::query()->count(), 'Editing must not create a second report.');
    }

    public function test_a_shared_report_stays_shared_and_the_player_sees_the_update(): void
    {
        $report = $this->saveReport(share: true);
        $sharedAt = $report->shared_at;
        Carbon::setTestNow(now()->addHour());

        $this->asCoach()->put(route('coach.reports.update', $report), $this->editPayload())
            ->assertSessionHas('status', 'Report updated. Riley Player will see the changes.');

        $report->refresh();
        $this->assertTrue($report->shared_with_player);
        $this->assertTrue($report->shared_at->equalTo($sharedAt));

        $this->actingAs($this->parent)->get('/player-dashboard')
            ->assertOk()
            ->assertSee('Clean first touch, even under real pressure.')
            ->assertSee('Strong');
    }

    public function test_a_private_report_can_be_shared_while_editing(): void
    {
        $report = $this->saveReport();

        $this->asCoach()->put(route('coach.reports.update', $report), $this->editPayload(['share' => '1']))
            ->assertSessionHas('status', 'Report updated and shared with Riley Player.');

        $this->assertTrue($report->fresh()->shared_with_player);
    }

    public function test_another_coach_cannot_open_or_change_the_report(): void
    {
        $report = $this->saveReport();
        $other = $this->coach('Other Coach');

        $this->asCoach($other)->get(route('coach.reports.edit', $report))->assertNotFound();
        $this->asCoach($other)->put(route('coach.reports.update', $report), $this->editPayload())->assertNotFound();
        $this->assertSame('Clean first touch under light pressure.', $report->fresh()->went_well);
    }

    public function test_the_coach_can_add_edit_and_remove_a_private_note(): void
    {
        $slug = $this->playerSlug();
        $page = '/coach/players/'.$slug;

        $this->asCoach()->get($page.'?tab=notes')->assertOk()->assertSee('No notes yet.')->assertSee('data-note-add', false);

        $this->asCoach()->post(route('coach.players.notes.store', $slug), ['body' => "Responds well to short cues.\nAsk about the knee."])
            ->assertRedirect(route('coach.players.show', ['player' => $slug, 'tab' => 'notes']))
            ->assertSessionHas('status', 'Note saved. Only you can see it.');

        $note = CoachPlayerNote::query()->sole();
        $this->assertSame($this->coach->id, $note->coach_id);
        $this->assertSame($this->parent->id, $note->athlete_id);
        $this->asCoach()->get($page.'?tab=notes')->assertSee('Responds well to short cues.')->assertSee('Casey Coach');

        Carbon::setTestNow(now()->addMinutes(5));
        $this->asCoach()->put(route('coach.players.notes.update', [$slug, $note]), ['body' => 'Knee is fine now.'])
            ->assertSessionHas('status', 'Note updated.');
        $this->assertSame('Knee is fine now.', $note->fresh()->body);
        $this->asCoach()->get($page.'?tab=notes')->assertSee('Knee is fine now.')->assertSee('Edited');

        $this->asCoach()->delete(route('coach.players.notes.destroy', [$slug, $note]))
            ->assertSessionHas('status', 'Note removed.');
        $this->assertSame(0, CoachPlayerNote::query()->count());
    }

    public function test_an_empty_note_is_refused_and_the_note_window_reopens(): void
    {
        $slug = $this->playerSlug();

        $this->asCoach()->post(route('coach.players.notes.store', $slug), ['body' => '  '])
            ->assertRedirect(route('coach.players.show', ['player' => $slug, 'tab' => 'notes']))
            ->assertSessionHasErrors('body', null, 'note');

        $this->asCoach()->followingRedirects()->post(route('coach.players.notes.store', $slug), ['body' => ''])
            ->assertSee('Write something before saving the note.')
            ->assertSee('id="coachNoteModal" aria-hidden="false"', false)
            ->assertDontSee('Couldn’t share video');
        $this->assertSame(0, CoachPlayerNote::query()->count());
    }

    public function test_notes_stay_private_to_the_coach_who_wrote_them(): void
    {
        $slug = $this->playerSlug();
        $this->asCoach()->post(route('coach.players.notes.store', $slug), ['body' => 'Private: parent asked to keep sessions short.']);
        $note = CoachPlayerNote::query()->sole();

        // A second coach who also trains Riley neither sees nor changes it.
        $other = $this->coach('Other Coach');
        Booking::query()->create([
            'reference' => 'CN-R2', 'coach_id' => $other->id, 'athlete_id' => $this->parent->id, 'location_id' => $this->park->id,
            'athlete_name' => 'Riley Player', 'session_type' => 'Private 1-on-1', 'session_date' => '2026-11-17', 'session_time' => '10:00:00',
            'duration_minutes' => 60, 'amount' => 40, 'status' => 'confirmed', 'payment_status' => 'paid',
        ]);
        $this->asCoach($other)->get('/coach/players/'.$slug.'?tab=notes')->assertOk()->assertDontSee('parent asked to keep sessions short');
        $this->asCoach($other)->put(route('coach.players.notes.update', [$slug, $note]), ['body' => 'changed'])->assertNotFound();
        $this->asCoach($other)->delete(route('coach.players.notes.destroy', [$slug, $note]))->assertNotFound();
        $this->assertSame('Private: parent asked to keep sessions short.', $note->fresh()->body);

        // The player never sees it.
        $this->actingAs($this->parent)->get('/player-dashboard')->assertOk()->assertDontSee('parent asked to keep sessions short');
    }
}
