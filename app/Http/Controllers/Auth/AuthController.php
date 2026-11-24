<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\User;
use App\Services\AppMailer;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->to(Auth::user()->dashboardPath());
        }

        $redirect = $request->query('redirect');
        if (is_string($redirect) && $redirect !== '') {
            $path = parse_url($redirect, PHP_URL_PATH) ?: '';
            $host = parse_url($redirect, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($path !== '' && ($host === null || $host === $appHost)) {
                $request->session()->put('url.intended', url($path.(parse_url($redirect, PHP_URL_QUERY) ? '?'.parse_url($redirect, PHP_URL_QUERY) : '')));
            }
        }

        return view('pages.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // One account guessed from one place, and one place guessing many accounts.
        $limits = [
            self::loginLimitKey($credentials['email'], $request->ip()) => [5, 300],
            'login-ip:'.$request->ip() => [30, 900],
        ];
        $this->stopIfTooManyTries($limits);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            foreach ($limits as $key => [, $seconds]) {
                RateLimiter::hit($key, $seconds);
            }

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear(array_key_first($limits));
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->dashboardPath());
    }

    public static function loginLimitKey(string $email, ?string $ip): string
    {
        return 'login:'.sha1(Str::lower($email).'|'.$ip);
    }

    /**
     * Checked before the password, so a correct guess during the wait doesn't get in either.
     *
     * @param  array<string, array{0: int, 1: int}>  $limits
     */
    private function stopIfTooManyTries(array $limits): void
    {
        foreach ($limits as $key => [$max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

                throw ValidationException::withMessages([
                    'email' => 'Too many tries. Please wait '.$minutes.' minute'.($minutes === 1 ? '' : 's').' and try again, or use Forgot password.',
                ]);
            }
        }
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->to(Auth::user()->dashboardPath());
        }

        return view('pages.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:40', new PhoneNumber],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:athlete,coach'],
            'sport' => ['required', 'string', Rule::in(User::SPORTS)],
            'specialty' => ['required_if:role,coach', 'nullable', 'string', Rule::in(Coach::SPECIALTIES)],
            'experience' => ['required_if:role,coach', 'nullable', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'bio' => ['required_if:role,coach', 'nullable', 'string', 'max:2000'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'sport' => $data['sport'],
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            if ($user->isCoach()) {
                $displayName = str_starts_with(strtolower($data['name']), 'coach ')
                    ? $data['name']
                    : 'Coach '.Str::of($data['name'])->before(' ')->toString();

                $coach = Coach::query()->create([
                    'user_id' => $user->id,
                    'display_name' => $displayName,
                    'status' => 'pending',
                    'sport' => $data['sport'],
                    'specialty' => $data['specialty'],
                    'experience' => $data['experience'],
                    'bio' => $data['bio'],
                ]);

                $user->setRelation('coach', $coach);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $mailer = app(AppMailer::class);
        $mailer->sendWelcome($user);

        if ($user->isCoach()) {
            $coach = $user->coach ?? Coach::query()->where('user_id', $user->id)->first();
            if ($coach) {
                $mailer->notifyAdminsOfPendingCoach($coach);
            }

            return redirect()
                ->route('coach.profile')
                ->with('success', 'Welcome! Finish your profile so an admin can approve your Find a Coach listing.');
        }

        return redirect()->to($user->dashboardPath());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
