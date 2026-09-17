<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\ContactMessage;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\SessionReport;
use App\Models\SharedVideo;
use App\Models\User;
use App\Services\AppMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $locations = Location::query()
            ->where('status', 'live')
            ->with(['coaches' => fn ($q) => $q->where('status', 'active')->orderBy('display_name')])
            ->orderBy('distance_miles')
            ->get()
            ->map(fn (Location $location) => [
                'id' => $location->slug,
                'name' => $location->name,
                'area' => $location->area,
                'distance' => (float) $location->distance_miles,
                'image' => $location->image_path,
                'coaches' => $location->coaches->map(fn (Coach $coach) => [
                    'id' => $coach->id,
                    'name' => $coach->display_name,
                    'specialty' => $coach->specialty,
                    'ages' => $coach->ages ?? 'All ages',
                    'price' => (int) $coach->rate,
                    'rating' => number_format((float) $coach->rating, 1),
                    'reviews' => (int) $coach->reviews_count,
                    'image' => $coach->photoUrl(),
                    'profileUrl' => route('coach-profile', $coach),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return view('pages.home', [
            'homeLocations' => $locations,
        ]);
    }

    public function findACoach(): View
    {
        $coaches = Coach::query()
            ->with('location')
            ->where('status', 'active')
            ->orderByDesc('rating')
            ->orderBy('display_name')
            ->get();

        $occupancy = Coach::occupancyByCoachIds($coaches->pluck('id')->all());

        return view('pages.find-a-coach', [
            'coaches' => $coaches,
            'occupancy' => $occupancy,
        ]);
    }

    public function becomeACoach(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user?->isCoach()) {
            return redirect()
                ->route('coach.profile')
                ->with('success', 'You’re already signed in as a coach.');
        }

        if ($user?->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Admin accounts don’t apply as coaches.');
        }

        if ($user?->isAthlete()) {
            return redirect()
                ->route('player-dashboard')
                ->with('error', 'Sign out first, then apply with a coach email.');
        }

        return view('pages.become-a-coach');
    }

    public function submitBecomeACoach(Request $request): RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->to(auth()->user()->dashboardPath())
                ->with('error', 'Sign out first to apply as a coach.');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'sport' => ['required', 'string', Rule::in(User::SPORTS)],
            'specialty' => ['required', 'string', Rule::in(Coach::SPECIALTIES)],
            'experience' => ['required', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'bio' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'sport' => $data['sport'],
                'password' => $data['password'],
                'role' => 'coach',
            ]);

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

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $mailer = app(AppMailer::class);
        $mailer->sendWelcome($user);
        $coach = $user->coach ?? Coach::query()->where('user_id', $user->id)->first();
        if ($coach) {
            $mailer->notifyAdminsOfPendingCoach($coach);
        }

        return redirect()
            ->route('coach.profile')
            ->with('success', 'Application received! Finish your profile so an admin can approve your Find a Coach listing.');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $fromHome = $request->input('from') === 'home';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'topic' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
            'agree' => ['accepted'],
        ]);

        ContactMessage::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'topic' => $data['topic'],
            'message' => $data['message'],
            'source' => $fromHome ? 'home' : 'contact',
        ]);

        app(AppMailer::class)->sendContactMessage([
            'name' => $data['name'],
            'email' => $data['email'],
            'topic' => $data['topic'],
            'message' => $data['message'],
            'source' => $fromHome ? 'Home' : 'Contact page',
        ]);

        $redirect = $fromHome
            ? redirect()->to(route('home').'#contact')
            : redirect()->route('contact');

        return $redirect->with('success', 'Thanks — your message was sent. We’ll get back to you soon.');
    }

    public function coachProfile(Coach $coach): View
    {
        $isOwner = auth()->check()
            && auth()->user()?->coach?->id === $coach->id;

        abort_unless($coach->status === 'active' || $isOwner, 404);

        $coach->loadMissing('location');

        return view('pages.coach-profile', [
            'coach' => $coach,
            'isOwnerPreview' => $isOwner && $coach->status !== 'active',
        ]);
    }

    public function playerDashboard(): View
    {
        $user = auth()->user();

        $upcoming = Booking::query()
            ->forAthlete($user->id)
            ->upcoming()
            ->with(['coach.user', 'location'])
            ->limit(8)
            ->get();

        $past = Booking::query()
            ->forAthlete($user->id)
            ->past()
            ->with(['coach.user', 'location'])
            ->limit(8)
            ->get();

        $completedCount = Booking::query()
            ->forAthlete($user->id)
            ->where('status', '!=', 'cancelled')
            ->whereDate('session_date', '<', now()->toDateString())
            ->count();

        $completedThisMonth = Booking::query()
            ->forAthlete($user->id)
            ->where('status', '!=', 'cancelled')
            ->whereDate('session_date', '<', now()->toDateString())
            ->whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->count();

        $nextBooking = $upcoming->first();
        $latestPast = $past->first();
        $focusBooking = $nextBooking ?? $latestPast;
        $focusLabel = $focusBooking?->session_type ?: 'Training';

        $hostedRequest = SessionRequest::query()
            ->where('requester_id', $user->id)
            ->whereIn('status', ['hosted', 'awaiting_deposit', 'confirmed'])
            ->whereNotNull('host_coach_id')
            ->with(['hostCoach', 'requestedCoach', 'location'])
            ->orderByDesc('created_at')
            ->first();

        $sessionRequests = SessionRequest::query()
            ->with(['hostCoach.user', 'requestedCoach.user', 'location', 'players'])
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                    ->orWhereHas('players', fn ($p) => $p->where('user_id', $user->id));
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (SessionRequest $session) => $session->toPlayerDashboardArray($user))
            ->values();

        $activeRequestCount = $sessionRequests
            ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
            ->count();
        $openRequestCount = $sessionRequests->where('status', 'open')->count();

        $sharedVideos = SharedVideo::query()
            ->with('coach.user')
            ->forAthlete($user)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map->toDisplayArray()
            ->values();

        $latestReport = SessionReport::query()
            ->with('coach.user')
            ->forAthlete($user)
            ->shared()
            ->orderByDesc('shared_at')
            ->orderByDesc('created_at')
            ->first();

        $parts = preg_split('/\s+/', trim($user->name)) ?: [];
        $initials = collect($parts)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') ?: 'PL';

        return view('pages.player-dashboard', [
            'user' => $user,
            'initials' => $initials,
            'upcomingBookings' => $upcoming,
            'pastBookings' => $past,
            'completedCount' => $completedCount,
            'completedThisMonth' => $completedThisMonth,
            'focusLabel' => $focusLabel,
            'nextBooking' => $nextBooking,
            'latestPast' => $latestPast,
            'hostedRequest' => $hostedRequest,
            'sessionRequests' => $sessionRequests,
            'activeRequestCount' => $activeRequestCount,
            'openRequestCount' => $openRequestCount,
            'sharedVideos' => $sharedVideos,
            'latestReport' => $latestReport?->toDisplayArray(),
        ]);
    }

    public function requestSession(Request $request): View
    {
        $locations = Location::query()
            ->where('status', 'live')
            ->withCount(['coaches' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('distance_miles')
            ->get();

        $requestedCoach = null;
        $coachId = $request->query('coach');
        if ($coachId) {
            $requestedCoach = Coach::query()
                ->with('location')
                ->where('id', $coachId)
                ->where('status', 'active')
                ->first();
        }

        $coaches = Coach::query()
            ->with('location')
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'location_id', 'rate', 'ages', 'specialty']);

        return view('pages.request-session', [
            'locations' => $locations,
            'requestedCoach' => $requestedCoach,
            'coaches' => $coaches,
            'preferredLocationSlug' => $requestedCoach?->location?->slug,
            'searchPrefill' => [
                'location' => trim((string) $request->query('location', '')),
                'date' => trim((string) $request->query('date', '')),
                'sport' => trim((string) $request->query('sport', '')),
                'session' => trim((string) $request->query('session', '')),
            ],
        ]);
    }
}
