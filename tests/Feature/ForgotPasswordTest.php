<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('password-forgot:127.0.0.1');
        RateLimiter::clear('password-reset:127.0.0.1');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role = 'athlete', string $email = 'pat@example.test'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Pat Parent', 'email' => $email, 'password' => 'old-password', 'role' => $role, 'phone' => '(951) 555-0100', 'email_verified_at' => now()])->save();

        return $user;
    }

    /** The token from the email that was sent. */
    private function askForLink(User $user): string
    {
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user, &$token) {
            $token = $mail->token;

            return $mail->hasTo($user->email);
        });

        return $token;
    }

    private function reset(string $token, string $email, string $password = 'brand-new-pass', ?string $confirm = null)
    {
        return $this->from('/reset-password/'.$token)->post('/reset-password', [
            'token' => $token, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm ?? $password,
        ]);
    }

    public function test_the_login_page_links_to_it(): void
    {
        $this->get('/login')->assertOk()->assertSee('Forgot password?')->assertSee(route('password.request'), false);
        $this->get('/forgot-password')->assertOk()->assertSee('Send reset link');
    }

    public function test_parents_coaches_and_the_admin_can_reset_their_password(): void
    {
        foreach (['athlete', 'coach', 'admin'] as $role) {
            $user = $this->user($role, "{$role}@example.test");
            $token = Password::createToken($user);

            $this->get("/reset-password/{$token}?email={$user->email}")->assertOk()->assertSee($user->email)->assertSee('no-referrer');
            $this->reset($token, $user->email)->assertRedirect('/login')->assertSessionHas('status');

            $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password), $role);
            $this->assertFalse(Auth::attempt(['email' => $user->email, 'password' => 'old-password']), $role);
            $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => 'brand-new-pass']), $role);
            Auth::logout();
        }
    }

    public function test_the_email_has_a_working_single_use_link(): void
    {
        $user = $this->user();
        $token = $this->askForLink($user);

        $mail = new PasswordResetMail($user, $token);
        $html = $mail->render();
        $this->assertStringContainsString(e(route('password.reset', ['token' => $token, 'email' => $user->email])), $html);
        $this->assertStringContainsString('expires in 60 minutes', $html);

        $this->reset($token, $user->email)->assertRedirect('/login');
        $this->reset($token, $user->email, 'second-try-pass')->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password), 'The link must not work twice.');
    }

    public function test_the_answer_is_the_same_for_an_unknown_email(): void
    {
        $user = $this->user();
        $known = $this->post('/forgot-password', ['email' => $user->email])->getSession()->get('status');
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.test'])->getSession()->get('status');

        $this->assertNotNull($known);
        $this->assertSame($known, $unknown);
        Mail::assertSent(PasswordResetMail::class, 1);
        Mail::assertNotSent(PasswordResetMail::class, fn ($m) => $m->hasTo('nobody@example.test'));
    }

    public function test_wrong_old_and_mismatched_attempts_are_refused(): void
    {
        $user = $this->user();
        $other = $this->user('athlete', 'other@example.test');
        $token = Password::createToken($user);

        $this->reset('not-the-token', $user->email)->assertSessionHasErrors('email');
        $this->reset($token, $other->email)->assertSessionHasErrors('email');
        $this->reset($token, $user->email, 'short')->assertSessionHasErrors('password');
        $this->reset($token, $user->email, 'brand-new-pass', 'something-else')->assertSessionHasErrors('password');

        Carbon::setTestNow(now()->addMinutes(61));
        $this->reset($token, $user->email)->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('old-password', $other->fresh()->password));
    }

    public function test_resetting_signs_the_person_out_everywhere(): void
    {
        $user = $this->user();
        $other = $this->user('athlete', 'other@example.test');
        foreach ([[$user->id, 'phone'], [$user->id, 'laptop'], [$other->id, 'theirs']] as [$id, $session]) {
            DB::table('sessions')->insert(['id' => $session, 'user_id' => $id, 'payload' => '', 'last_activity' => time()]);
        }
        $user->forceFill(['remember_token' => 'old-remember'])->save();

        // Works on a device that is still signed in, and signs that one out too.
        $this->actingAs($user);
        $token = Password::createToken($user);
        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk();
        $this->reset($token, $user->email)->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame(['theirs'], DB::table('sessions')->whereIn('id', ['phone', 'laptop', 'theirs'])->pluck('id')->all());
        $this->assertNotSame('old-remember', $user->fresh()->remember_token);
    }

    public function test_it_cannot_be_used_to_flood_someones_inbox(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        }
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasErrors('email');

        Mail::assertSent(PasswordResetMail::class, 1);
    }

    public function test_the_link_always_points_to_our_own_site(): void
    {
        config(['app.url' => 'https://coachnow.online']);
        \Illuminate\Support\Facades\URL::forceRootUrl('https://coachnow.online');
        \Illuminate\Support\Facades\URL::forceScheme('https');
        $user = $this->user();

        $this->call('POST', '/forgot-password', ['email' => $user->email], [], [], ['HTTP_HOST' => 'evil.example']);

        Mail::assertSent(PasswordResetMail::class, fn (PasswordResetMail $m) => str_contains($m->render(), 'https://coachnow.online/reset-password/') && ! str_contains($m->render(), 'evil.example'));
    }
}
