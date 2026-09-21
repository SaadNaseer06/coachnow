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
use App\Services\NominatimGeocoder;
use Illuminate\Http\JsonResponse;
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
                    'id' => $coach->publicToken(),
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

    public function findACoach(Request $request, NominatimGeocoder $geocoder): View|JsonResponse
    {
        $locationQuery = trim((string) $request->query('location', ''));
        $sportFilter = trim((string) $request->query('sport', ''));
        $sessionFilter = trim((string) $request->query('session', ''));
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $minRating = (float) $request->query('rating', 0);
        $experienceFilters = array_values(array_filter((array) $request->query('experience', [])));
        $ageFilters = array_values(array_filter((array) $request->query('age', [])));

        $originLat = $request->query('lat');
        $originLng = $request->query('lng');
        $searchOrigin = null;
        $geocodeFailed = false;
        $radiusExpanded = false;
        $effectiveRadius = null;

        if (is_numeric($originLat) && is_numeric($originLng)) {
            $isNearMe = $locationQuery === '' || preg_match('/^(near me|current location)$/i', $locationQuery);
            $searchOrigin = [
                'lat' => (float) $originLat,
                'lng' => (float) $originLng,
                'label' => $isNearMe ? 'you' : $locationQuery,
            ];
        } elseif ($locationQuery !== '' && ! preg_match('/^(near me|current location)$/i', $locationQuery)) {
            $geocoded = $geocoder->geocode($locationQuery);
            if ($geocoded) {
                $searchOrigin = $geocoded;
                $searchOrigin['label'] = $this->shortGeocodeLabel($geocoded['label'], $locationQuery);
            } else {
                $geocodeFailed = true;
            }
        } elseif (preg_match('/^(near me|current location)$/i', $locationQuery)) {
            // "Near me" without coords — ask the browser for location instead of geocoding the phrase
            $geocodeFailed = false;
            $searchOrigin = null;
        }

        $coaches = Coach::query()
            ->with('location')
            ->where('status', 'active')
            ->get();

        if ($searchOrigin) {
            $this->ensureCoachLocationCoordinates($coaches, $geocoder);
        }

        if ($sportFilter !== '') {
            $coaches = $coaches->filter(function (Coach $coach) use ($sportFilter) {
                return $this->coachSportSlug($coach) === strtolower($sportFilter);
            })->values();
        }

        if ($sessionFilter !== '' && $sessionFilter !== 'all') {
            $coaches = $coaches->filter(function (Coach $coach) use ($sessionFilter) {
                $sessions = explode(' ', $this->coachSessionTags($coach));

                return in_array(strtolower($sessionFilter), $sessions, true);
            })->values();
        }

        if (is_numeric($minPrice)) {
            $coaches = $coaches->filter(fn (Coach $c) => (float) $c->rate >= (float) $minPrice)->values();
        }
        if (is_numeric($maxPrice)) {
            $coaches = $coaches->filter(fn (Coach $c) => (float) $c->rate <= (float) $maxPrice)->values();
        }
        if ($minRating > 0) {
            $coaches = $coaches->filter(fn (Coach $c) => (float) $c->rating >= $minRating)->values();
        }
        if ($experienceFilters !== []) {
            $coaches = $coaches->filter(function (Coach $coach) use ($experienceFilters) {
                return in_array($this->coachExperienceBucket($coach), $experienceFilters, true);
            })->values();
        }
        if ($ageFilters !== []) {
            $coaches = $coaches->filter(function (Coach $coach) use ($ageFilters) {
                $tags = preg_split('/\s+/', $coach->ageFilterTags(), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                return count(array_intersect($ageFilters, $tags)) > 0;
            })->values();
        }

        $ranked = $coaches->map(function (Coach $coach) use ($searchOrigin) {
            $distance = null;
            if ($searchOrigin && $coach->location) {
                $distance = $coach->location->distanceMilesFrom(
                    $searchOrigin['lat'],
                    $searchOrigin['lng']
                );
            }
            $coach->setAttribute('computed_distance_miles', $distance);

            return $coach;
        });

        $beyondRadius = false;
        $nearestDistance = null;
        $missingCoords = false;

        if ($searchOrigin) {
            $withDistance = $ranked
                ->filter(fn (Coach $c) => $c->computed_distance_miles !== null)
                ->values();

            if ($withDistance->isEmpty()) {
                // Parks have no coordinates — cannot honestly run a near-me search
                $missingCoords = true;
                $coaches = collect();
                $effectiveRadius = null;
                $radiusExpanded = false;
            } else {
                $radii = [25, 50, 100];
                $within = collect();
                foreach ($radii as $radius) {
                    $within = $withDistance->filter(function (Coach $coach) use ($radius) {
                        return $coach->computed_distance_miles <= $radius;
                    })->values();

                    if ($within->isNotEmpty()) {
                        $effectiveRadius = $radius;
                        $radiusExpanded = $radius > 25;
                        break;
                    }
                }

                if ($within->isEmpty()) {
                    // Nothing within 100 mi — show nearest options with honest messaging
                    $within = $withDistance
                        ->sortBy('computed_distance_miles')
                        ->take(8)
                        ->values();
                    $beyondRadius = true;
                    $nearestDistance = $within->first()?->computed_distance_miles;
                    $effectiveRadius = null;
                    $radiusExpanded = false;
                } else {
                    $within = $within->sortBy('computed_distance_miles')->values();
                }

                // Never dump "no coords" coaches into a near-me result set
                $coaches = $within->values();
            }
        } else {
            $coaches = $ranked
                ->sortBy([
                    ['rating', 'desc'],
                    ['display_name', 'asc'],
                ])
                ->values();
        }

        $perPage = 9;
        $page = max(1, (int) $request->query('page', 1));
        $total = $coaches->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $coaches->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => route('find-a-coach'),
                'query' => collect($request->query())->except(['ajax'])->all(),
            ]
        );
        $paginated->withPath(route('find-a-coach'));

        $occupancy = Coach::occupancyByCoachIds($paginated->getCollection()->pluck('id')->all());

        $viewData = [
            'coaches' => $paginated,
            'occupancy' => $occupancy,
            'searchOrigin' => $searchOrigin,
            'effectiveRadius' => $effectiveRadius,
            'radiusExpanded' => $radiusExpanded,
            'beyondRadius' => $beyondRadius,
            'nearestDistance' => $nearestDistance,
            'missingCoords' => $missingCoords,
            'geocodeFailed' => $geocodeFailed,
            'needsLocation' => (bool) (preg_match('/^(near me|current location)$/i', $locationQuery) && ! $searchOrigin),
            'filters' => [
                'location' => $locationQuery === '' || preg_match('/^(near me|current location)$/i', $locationQuery)
                    ? ($searchOrigin ? 'Near me' : '')
                    : $locationQuery,
                'sport' => $sportFilter,
                'session' => $sessionFilter,
                'min_price' => is_numeric($minPrice) ? $minPrice : '',
                'max_price' => is_numeric($maxPrice) ? $maxPrice : '',
                'rating' => $minRating > 0 ? (string) (int) $minRating : '0',
                'experience' => $experienceFilters,
                'age' => $ageFilters,
                'lat' => isset($searchOrigin['lat']) ? (string) $searchOrigin['lat'] : '',
                'lng' => isset($searchOrigin['lng']) ? (string) $searchOrigin['lng'] : '',
            ],
        ];

        if ($request->boolean('ajax') || $request->wantsJson()) {
            return response()->json([
                'html' => view('pages.partials.find-a-coach-results', $viewData)->render(),
                'filters' => $viewData['filters'],
                'count' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ]);
        }

        return view('pages.find-a-coach', $viewData);
    }

    private function shortGeocodeLabel(string $displayName, string $fallback): string
    {
        $parts = array_map('trim', explode(',', $displayName));
        if (count($parts) >= 2) {
            return $parts[0].', '.$parts[1];
        }

        return $fallback !== '' ? $fallback : $displayName;
    }

    /**
     * Geocode and persist missing park coordinates so Near me can compute distance.
     * Limited + cached to stay within Nominatim fair-use on live.
     *
     * @param  \Illuminate\Support\Collection<int, Coach>  $coaches
     */
    private function ensureCoachLocationCoordinates($coaches, NominatimGeocoder $geocoder): void
    {
        $missing = $coaches
            ->map(fn (Coach $c) => $c->location)
            ->filter(fn ($location) => $location && ! $location->hasCoordinates())
            ->unique('id')
            ->take(8)
            ->values();

        foreach ($missing as $location) {
            $coords = $geocoder->geocodePark((string) $location->name, (string) $location->area);
            if (! $coords) {
                continue;
            }

            $location->forceFill([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
            ])->save();
        }
    }

    private function coachSportSlug(Coach $coach): string
    {
        $sport = User::sportFilterValue($coach->sport);
        if ($sport !== '') {
            return $sport;
        }

        $specialty = strtolower((string) $coach->specialty);
        if (str_contains($specialty, 'futsal')) {
            return 'futsal';
        }
        if (str_contains($specialty, 'performance') || str_contains($specialty, 'speed')) {
            return 'fitness';
        }

        return '';
    }

    private function coachSessionTags(Coach $coach): string
    {
        $specialty = strtolower((string) $coach->specialty);

        return match (true) {
            str_contains($specialty, 'group') => 'group semi-private',
            str_contains($specialty, 'team') || str_contains($specialty, 'clinic') => 'group camp',
            str_contains($specialty, '1-on-1') || str_contains($specialty, 'private') => '1on1',
            default => '1on1',
        };
    }

    private function coachExperienceBucket(Coach $coach): string
    {
        return match (true) {
            str_starts_with((string) $coach->experience, '1-3') => '1-3',
            str_starts_with((string) $coach->experience, '4-5') => '4-5',
            str_starts_with((string) $coach->experience, '6-7') => '6-7',
            default => '8-10',
        };
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

    public function coachProfile(Request $request, Coach $coach): View|RedirectResponse
    {
        $raw = $request->route()->originalParameter('coach');
        if (ctype_digit((string) $raw) && filled($coach->slug)) {
            return redirect()->route('coach-profile', $coach, 301);
        }

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
        $focusLabel = $focusBooking?->session_type ?: 'No priority yet';

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

        $skillProgress = [];
        if ($latestReport) {
            $skillProgress = self::skillProgressFromReport($latestReport);
        }

        if ($completedCount === 0) {
            $progressLabel = 'Getting started';
            $progressTone = 'zinc';
            $progressNote = 'Book your first session';
        } elseif ($completedCount < 3) {
            $progressLabel = 'Building momentum';
            $progressTone = 'amber';
            $progressNote = 'Keep training consistently';
        } else {
            $progressLabel = 'Improving steadily';
            $progressTone = 'green';
            $progressNote = 'On track';
        }

        $milestones = [
            [
                'label' => 'First session',
                'earned' => $completedCount >= 1,
                'icon' => 'bolt',
            ],
            [
                'label' => '5 sessions',
                'earned' => $completedCount >= 5,
                'icon' => 'star',
            ],
            [
                'label' => '10 sessions',
                'earned' => $completedCount >= 10,
                'icon' => 'lock',
            ],
        ];

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
            'skillProgress' => $skillProgress,
            'skillUpdatedAt' => $latestReport?->shared_at?->format('M j')
                ?? $latestReport?->created_at?->format('M j'),
            'progressLabel' => $progressLabel,
            'progressTone' => $progressTone,
            'progressNote' => $progressNote,
            'milestones' => $milestones,
        ]);
    }

    /**
     * Build a small skill snapshot from a shared coach report (no fake defaults).
     *
     * @return list<array{skill: string, tone: string, label: string}>
     */
    private static function skillProgressFromReport(SessionReport $report): array
    {
        $items = [];

        $keywords = collect(preg_split('/[,|\/]+/', (string) $report->keywords) ?: [])
            ->map(fn ($k) => trim($k))
            ->filter()
            ->take(3)
            ->values();

        foreach ($keywords as $keyword) {
            $items[] = [
                'skill' => Str::title($keyword),
                'tone' => 'yellow',
                'label' => 'Developing',
            ];
        }

        $focus = trim((string) $report->focus);
        if ($focus !== '' && count($items) < 3) {
            $focusSkill = Str::limit(Str::of($focus)->before('.')->trim()->toString(), 40, '');
            if ($focusSkill !== '' && ! collect($items)->contains(fn ($i) => strcasecmp($i['skill'], $focusSkill) === 0)) {
                $items[] = [
                    'skill' => $focusSkill,
                    'tone' => 'yellow',
                    'label' => 'Focus area',
                ];
            }
        }

        if ($items === [] && filled($report->went_well)) {
            $items[] = [
                'skill' => 'Session strengths',
                'tone' => 'green',
                'label' => 'Strong',
            ];
        }

        if (filled($report->needs_work)) {
            $items[] = [
                'skill' => 'Needs work',
                'tone' => 'red',
                'label' => 'Needs work',
            ];
        }

        return array_slice($items, 0, 5);
    }

    public function requestSession(Request $request): View|RedirectResponse
    {
        $locations = Location::query()
            ->where('status', 'live')
            ->withCount(['coaches' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('distance_miles')
            ->get();

        $requestedCoach = null;
        $coachToken = $request->query('coach');
        if ($coachToken) {
            $resolved = Coach::resolveFromPublicToken((string) $coachToken);
            if ($resolved && $resolved->status === 'active') {
                $requestedCoach = $resolved->loadMissing('location');
            }

            // Prefer slug in the address bar for shareable links
            if ($requestedCoach && filled($requestedCoach->slug) && (string) $coachToken !== (string) $requestedCoach->slug) {
                return redirect()->route('request-session', array_merge(
                    $request->except('coach'),
                    ['coach' => $requestedCoach->slug]
                ), 301);
            }
        }

        $coaches = Coach::query()
            ->with('location')
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'slug', 'location_id', 'rate', 'ages', 'specialty']);

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
