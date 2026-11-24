<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LoginLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'pat@example.test', string $role = 'athlete'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Pat Parent', 'email' => $email, 'password' => 'right-password', 'role' => $role, 'phone' => '(951) 555-0100', 'email_verified_at' => now()])->save();

        return $user;
    }

    private function login(string $email, string $password, string $ip = '10.0.0.1'): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/login')->post('/login', ['email' => $email, 'password' => $password]);
    }

    private function assertBlocked(TestResponse $response): void
    {
        $response->assertRedirect('/login');
        $this->assertStringContainsString('Too many tries', (string) session('errors')?->first('email'));
        $this->assertGuest();
    }

    public function test_five_wrong_tries_then_a_wait_even_with_the_right_password(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->login($user->email, 'wrong');
            $this->assertStringContainsString('do not match', (string) session('errors')->first('email'));
        }

        $this->assertBlocked($this->login($user->email, 'right-password'));
        $this->assertStringContainsString('5 minutes', (string) session('errors')->first('email'));
        $this->get('/login')->assertOk()->assertSee('Forgot password?');

        Carbon::setTestNow(now()->addSeconds(301));
        $this->login($user->email, 'right-password')->assertRedirect('/player-dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_it_protects_the_admin_and_coaches_too(): void
    {
        foreach (['admin', 'coach'] as $role) {
            $user = $this->user("{$role}@example.test", $role);
            for ($i = 0; $i < 5; $i++) {
                $this->login($user->email, 'wrong');
            }
            $this->assertBlocked($this->login($user->email, 'right-password'));
        }
    }

    public function test_a_correct_sign_in_resets_the_count(): void
    {
        $user = $this->user();
        for ($round = 0; $round < 2; $round++) {
            for ($i = 0; $i < 4; $i++) {
                $this->login($user->email, 'wrong');
            }
            $this->login($user->email, 'right-password')->assertRedirect('/player-dashboard');
            $this->post('/logout');
        }
    }

    public function test_resetting_the_password_ends_the_wait(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->login($user->email, 'wrong');
        }
        $this->assertBlocked($this->login($user->email, 'right-password'));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->post('/reset-password', [
            'token' => Password::createToken($user), 'email' => $user->email, 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect('/login');

        $this->login($user->email, 'brand-new-pass')->assertRedirect('/player-dashboard');
    }

    public function test_capital_letters_in_the_email_do_not_get_around_it(): void
    {
        $user = $this->user();
        foreach (['pat@example.test', 'PAT@example.test', 'Pat@Example.test', 'pAt@example.test', 'pat@EXAMPLE.test'] as $email) {
            $this->login($email, 'wrong');
        }

        $this->assertBlocked($this->login($user->email, 'right-password'));
    }

    public function test_other_people_are_not_locked_out(): void
    {
        $user = $this->user();
        $other = $this->user('other@example.test');
        for ($i = 0; $i < 5; $i++) {
            $this->login($user->email, 'wrong', '10.9.9.9');
        }

        // The real owner, somewhere else, can still get in.
        $this->login($user->email, 'right-password', '10.0.0.2')->assertRedirect('/player-dashboard');
        $this->post('/logout');
        $this->login($other->email, 'right-password', '10.9.9.9')->assertRedirect('/player-dashboard');
    }

    public function test_trying_many_different_emails_from_one_place_is_stopped(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 30; $i++) {
            $this->login("guess{$i}@example.test", 'wrong');
        }

        $this->assertBlocked($this->login($user->email, 'right-password'));
        $this->login($user->email, 'right-password', '10.0.0.3')->assertRedirect('/player-dashboard');
    }
}
