<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\ContactMessage;
use App\Models\GroupJoinRequest;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\SessionReport;
use App\Models\SharedVideo;
use App\Models\User;
use App\Services\AppMailer;
use App\Services\NominatimGeocoder;
use App\Services\TrainingNearYouService;
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
    public function home(Request $request): View
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

        $origin = null;
        if ($request->filled(['lat', 'lng'])) {
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');
            if (is_finite($lat) && is_finite($lng) && abs($lat) <= 90 && abs($lng) <= 180) {
                $origin = ['lat' => $lat, 'lng' => $lng];
            }
        }

        $featuredSessions = app(TrainingNearYouService::class)->featured($origin, 4);
        $featuredNearYou = $origin !== null && collect($featuredSessions)->contains(
            fn (array $row) => ($row['distance_miles'] ?? null) !== null
        );

        return view('pages.home', [
            'homeLocations' => $locations,
            'featuredSessions' => $featuredSessions,
            'featuredNearYou' => $featuredNearYou,
            'featuredTriedGeo' => $origin !== null,
        ]);
    }

    public function trainingNearYou(Request $request, NominatimGeocoder $geocoder, TrainingNearYouService $discovery): View
    {
        $locationQuery = trim((string) $request->query('location', ''));
        $radius = (int) $request->query('radius', 5);
        if (! in_array($radius, [5, 10, 20], true)) {
            $radius = 5;
        }
        $sport = trim((string) $request->query('sport', ''));
        $age = trim((string) $request->query('age', ''));

        $originLat = $request->query('lat');
        $originLng = $request->query('lng');
        $searchOrigin = null;
        $geocodeFailed = false;

        if (is_numeric($originLat) && is_numeric($originLng)) {
            $searchOrigin = [
                'lat' => (float) $originLat,
                'lng' => (float) $originLng,
                'label' => $locationQuery !== '' ? $locationQuery : 'you',
            ];
        } elseif ($locationQuery !== '' && ! preg_match('/^(near me|current location)$/i', $locationQuery)) {
            $geocoded = $geocoder->geocode($locationQuery);
            if ($geocoded) {
                $searchOrigin = $geocoded;
                $searchOrigin['label'] = $this->shortGeocodeLabel($geocoded['label'], $locationQuery);
            } else {
                $geocodeFailed = true;
            }
        }

        if ($searchOrigin) {
            $locations = Location::query()->where('status', 'live')->get();
            foreach ($locations as $location) {
                if ($location->hasCoordinates()) {
                    continue;
                }
                $coords = $geocoder->geocode(trim($location->name.' '.$location->area));
                if ($coords) {
                    $location->forceFill([
                        'latitude' => $coords['lat'],
                        'longitude' => $coords['lng'],
                    ])->save();
                }
            }
        }

        $openGroups = $discovery->openGroupSessions($searchOrigin, $radius, $sport ?: null, $age ?: null);
        $openRequests = $discovery->openSessionRequests($searchOrigin, $radius, $sport ?: null, $age ?: null);

        return view('pages.training-near-you', [
            'openGroups' => $openGroups,
            'openRequests' => $openRequests,
            'radius' => $radius,
            'sport' => $sport,
            'age' => $age,
            'locationQuery' => $locationQuery,
            'searchOrigin' => $searchOrigin,
            'geocodeFailed' => $geocodeFailed,
            'sports' => User::SPORTS,
            'ageOptions' => Coach::AGE_OPTIONS,
            'filters' => [
                'location' => $locationQuery,
                'lat' => $searchOrigin['lat'] ?? $request->query('lat'),
                'lng' => $searchOrigin['lng'] ?? $request->query('lng'),
                'radius' => $radius,
                'sport' => $sport,
                'age' => $age,
            ],
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
            'phone' => ['required', 'string', 'max:40'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => ['string', Rule::in(User::SPORTS)],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*' => ['string', Rule::in(Coach::SPECIALTIES)],
            'experience' => ['required', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'bio' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $sports = array_values(array_unique($data['sports']));
        $specialties = array_values(array_unique($data['specialties']));

        $user = DB::transaction(function () use ($data, $sports, $specialties) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'sport' => $sports[0],
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
                'sport' => $sports[0],
                'sports' => $sports,
                'specialty' => $specialties[0],
                'specialties' => $specialties,
                'experience' => $data['experience'],
                'bio' => $data['bio'],
                'plan' => 'trial',
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

        $coach->loadMissing(['location', 'availabilitySlots.location']);

        $weeklyAvailability = $coach->availabilitySlots
            ->filter(fn ($slot) => (bool) $slot->is_active)
            ->sortBy(['day_of_week', 'start_time'])
            ->values();

        $openSlots = app(\App\Services\CoachAvailabilityService::class)
            ->openSlotsForCoach($coach, now()->startOfDay(), now()->addDays(14)->startOfDay());

        return view('pages.coach-profile', [
            'coach' => $coach,
            'isOwnerPreview' => $isOwner && $coach->status !== 'active',
            'weeklyAvailability' => $weeklyAvailability,
            'openSlotCount' => count($openSlots),
        ]);
    }

    public function bookCoach(Request $request, Coach $coach): View|RedirectResponse
    {
        $raw = $request->route()->originalParameter('coach');
        if (ctype_digit((string) $raw) && filled($coach->slug)) {
            return redirect()->route('book-coach', array_filter([
                'coach' => $coach,
                'group' => $request->query('group'),
            ]), 301);
        }

        abort_unless($coach->status === 'active', 404);
        $coach->loadMissing('location');

        $slots = app(\App\Services\CoachAvailabilityService::class)
            ->openSlotsForCoach($coach, now()->startOfDay(), now()->addDays(14)->startOfDay());

        $dates = collect($slots)->pluck('date')->unique()->values()->all();
        $focusGroupId = $request->integer('group') ?: null;

        return view('pages.book-coach', [
            'coach' => $coach,
            'slots' => $slots,
            'availableDates' => $dates,
            'slotsJson' => $slots,
            'focusGroupId' => $focusGroupId,
            'convenienceFee' => User::convenienceFeeFor(request()->user()),
        ]);
    }

    /**
     * Public view of an open marketplace request (from Training Near You) where
     * another family can join. Requests sent to specific coaches are never shown.
     */
    public function openSessionRequest(Request $request, string $reference): View
    {
        $session = SessionRequest::query()
            ->with(['players', 'location', 'hostCoach'])
            ->where('reference', $reference)
            ->firstOrFail();

        abort_if($session->isTargeted(), 404);
        abort_unless(in_array($session->status, ['open', 'hosted', 'awaiting_deposit', 'confirmed'], true), 404);

        $user = $request->user();
        $joinedCount = $session->players->count();
        $isFull = $session->max_players !== null && $joinedCount >= (int) $session->max_players;
        $alreadyIn = $user && $session->players->contains(fn ($p) => (int) $p->user_id === (int) $user->id);

        return view('pages.open-session-request', [
            'session' => $session,
            'joinedCount' => $joinedCount,
            'isFull' => $isFull,
            'alreadyIn' => $alreadyIn,
            'joinClosed' => $session->isJoinClosed(),
            'depositAmount' => (float) config('coachnow.payments.deposit_amount', 10),
            'convenienceFee' => User::convenienceFeeFor(request()->user()),
        ]);
    }

    public function sharedGroupSession(string $token): View|RedirectResponse
    {
        $group = CoachGroupSession::query()
            ->with(['coach.user', 'location'])
            ->where('share_token', $token)
            ->firstOrFail();

        abort_unless($group->is_public && $group->status !== 'cancelled', 404);
        abort_unless($group->coach && $group->coach->status === 'active', 404);

        $meta = $group->toParentSlotArray();

        return view('pages.shared-group-session', [
            'group' => $group,
            'coach' => $group->coach,
            'meta' => $meta,
            'bookUrl' => route('book-coach', [
                'coach' => $group->coach->publicToken(),
                'group' => $group->id,
            ]),
            'convenienceFee' => User::convenienceFeeFor(request()->user()),
        ]);
    }

    public function playerDashboard(): View
    {
        $user = auth()->user();

        $upcoming = Booking::query()
            ->forAthlete($user->id)
            ->upcoming()
            ->with(['coach.user', 'location', 'groupSession'])
            ->limit(8)
            ->get();

        $past = Booking::query()
            ->forAthlete($user->id)
            ->past()
            ->with(['coach.user', 'location', 'groupSession'])
            ->limit(8)
            ->get();

        $trainingHistory = $this->trainingHistory($user);

        $groupJoined = Booking::query()
            ->forAthlete($user->id)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('group_session_id')
            ->with(['coach.user', 'location', 'groupSession'])
            ->orderByDesc('session_date')
            ->orderByDesc('session_time')
            ->limit(12)
            ->get();

        $pendingGroupJoins = GroupJoinRequest::query()
            ->where('athlete_id', $user->id)
            ->where('status', 'pending')
            ->with(['groupSession.coach.user', 'groupSession.location'])
            ->latest()
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
            'trainingHistory' => $trainingHistory,
            // Same layout for everyone; member panels show a locked preview for non-members.
            'memberAccess' => $user->hasMemberAccess(),
            'membership' => [
                'active' => $user->hasActiveSubscription(),
                'ends_at' => $user->subscription_ends_at?->format('M j, Y'),
                'cancelling' => (bool) $user->subscription_cancel_at_period_end,
                'price' => (float) config('coachnow.subscription.price', 10),
                'fee' => (float) config('coachnow.payments.convenience_fee', 2),
            ],
            'groupJoinedBookings' => $groupJoined,
            'pendingGroupJoins' => $pendingGroupJoins,
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
     * Completed + upcoming sessions in chronological order. Each completed session is
     * matched to the shared report its coach wrote for this player on or after that
     * day (before their next session together), so parents can open it from the list.
     *
     * @return list<array<string, mixed>>
     */
    private function trainingHistory(User $user): array
    {
        $completed = Booking::query()
            ->forAthlete($user->id)
            ->past()
            ->with(['coach', 'location'])
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        $upcoming = Booking::query()
            ->forAthlete($user->id)
            ->upcoming()
            ->with(['coach', 'location'])
            ->limit(10)
            ->get();

        $reports = SessionReport::query()
            ->with('coach.user')
            ->forAthlete($user)
            ->shared()
            ->orderBy('created_at')
            ->get();

        $used = [];
        $rows = [];
        foreach ($completed as $i => $booking) {
            $from = $booking->session_date?->copy()->startOfDay();
            $next = $completed->slice($i + 1)->first(fn ($b) => (int) $b->coach_id === (int) $booking->coach_id);
            $until = $next?->session_date?->copy()->startOfDay();

            $report = $from ? $reports->first(fn (SessionReport $r) => ! isset($used[$r->id])
                && (int) $r->coach_id === (int) $booking->coach_id
                && $r->created_at?->gte($from)
                && (! $until || $r->created_at->lt($until))) : null;
            if ($report) {
                $used[$report->id] = true;
            }

            $rows[] = ['booking' => $booking, 'upcoming' => false, 'report' => $report?->toDisplayArray()];
        }

        foreach ($upcoming as $booking) {
            $rows[] = ['booking' => $booking, 'upcoming' => true, 'report' => null];
        }

        return $rows;
    }

    /**
     * Build a small skill snapshot from a shared coach report (no fake defaults).
     *
     * @return list<array{skill: string, tone: string, label: string}>
     */
    private static function skillProgressFromReport(SessionReport $report): array
    {
        // Statuses the coach chose on the report win. Older reports (before the picker)
        // fall back to keywords, which were always shown as "Developing".
        $chosen = collect($report->skill_statuses ?? [])
            ->filter(fn ($row) => filled($row['skill'] ?? null))
            ->map(fn ($row) => match ($row['status'] ?? 'developing') {
                'strong' => ['skill' => Str::title($row['skill']), 'tone' => 'green', 'label' => 'Strong'],
                'needs_work' => ['skill' => Str::title($row['skill']), 'tone' => 'red', 'label' => 'Needs work'],
                default => ['skill' => Str::title($row['skill']), 'tone' => 'yellow', 'label' => 'Developing'],
            })
            ->values()
            ->all();
        if ($chosen !== []) {
            return array_slice($chosen, 0, 5);
        }

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
        if (! $request->user()?->hasMemberAccess()) {
            return view('pages.membership-required', ['feature' => 'Request a Session']);
        }

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
