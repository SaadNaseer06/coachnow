<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\User;
use App\Services\AppMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->to(Auth::user()->dashboardPath());
        }

        return view('pages.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->dashboardPath());
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
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:athlete,coach'],
            'specialty' => ['required_if:role,coach', 'nullable', 'string', Rule::in(Coach::SPECIALTIES)],
            'experience' => ['required_if:role,coach', 'nullable', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'bio' => ['required_if:role,coach', 'nullable', 'string', 'max:2000'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
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
