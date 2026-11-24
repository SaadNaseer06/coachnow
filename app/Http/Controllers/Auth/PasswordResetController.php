<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('pages.forgot-password');
    }

    /**
     * The answer is the same whether or not the email has an account, and the email goes out
     * after the response, so neither the text nor the timing gives it away.
     */
    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $this->limit($request, 'forgot');

        dispatch(function () use ($data) {
            Password::sendResetLink($data);
        })->afterResponse();

        return back()
            ->withInput()
            ->with('status', 'If that email has a CoachNow account, we’ve sent a link to reset the password. It can take a few minutes, so check your spam folder too.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('pages.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        $this->limit($request, 'reset');

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            // Anyone still signed in to this account, on any device, has to use the new password.
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This reset link is no longer valid. Please ask for a new one.']);
        }

        // Locked out after wrong tries? The new password should work straight away.
        RateLimiter::clear(AuthController::loginLimitKey($data['email'], $request->ip()));

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Your password has been changed. Sign in with your new password.');
    }

    private function limit(Request $request, string $what): void
    {
        $key = "password-{$what}:".$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            throw ValidationException::withMessages(['email' => 'Too many tries. Please wait a few minutes and try again.']);
        }
        RateLimiter::hit($key, 300);
    }
}
