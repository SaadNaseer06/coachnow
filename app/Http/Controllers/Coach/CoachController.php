<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\SharedVideo;
use App\Models\User;
use App\Services\SessionBookingService;
use App\Services\VideoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoachController extends Controller
{
    /** Calendar spans 4:00 AM – 9:00 PM in 30 minute rows (matches admin-schedule.css). */
    private const CAL_START_HOUR = 4;

    private const CAL_END_HOUR = 21;

    public function schedule(Request $request): View
    {
        $coach = $this->currentCoach();
        app(SessionBookingService::class)->syncMissingForCoach($coach);

        $weekStart = $this->resolveWeekStart($request->query('week'));
        $weekExplicit = $request->filled('week');

        if (! $weekExplicit) {
            $weekStart = $this->preferWeekWithSessions($coach, $weekStart);
        }

        $bookings = Booking::query()
            ->forCoach($coach->id)
            ->inWeek($weekStart)
            ->confirmed()
            ->with(['athlete', 'location'])
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get();

        $sessions = $this->mapBookingsToCalendar($bookings, $weekStart);
        $uniquePlayers = $bookings->map(fn (Booking $b) => $b->athlete_id ?: Str::lower($b->displayName()))->unique()->count();
        $hours = round($bookings->sum(fn (Booking $b) => ($b->duration_minutes ?? 60) / 60), 1);

        $upcomingTotal = Booking::query()
            ->forCoach($coach->id)
            ->confirmed()
            ->upcoming()
            ->count();

        return view('coach.schedule', [
            'weekLabel' => $weekStart->format('M j').' – '.$weekStart->copy()->addDays(6)->format('M j, Y'),
            'weekStart' => $weekStart->toDateString(),
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'hours' => range(self::CAL_START_HOUR, self::CAL_END_HOUR - 1),
            'days' => $this->calendarDays($weekStart),
            'sessions' => $sessions,
            'upcomingTotal' => $upcomingTotal,
            'summary' => [
                ['label' => 'Sessions', 'value' => (string) $bookings->count(), 'note' => 'Booked this week'],
                ['label' => 'Players', 'value' => (string) $uniquePlayers, 'note' => 'Across all sessions'],
                ['label' => 'Hours', 'value' => (string) $hours, 'note' => 'On the field'],
            ],
        ]);
    }

    public function dashboard(): View
    {
        $coach = $this->currentCoach();
        app(SessionBookingService::class)->syncMissingForCoach($coach);
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        $players = $this->rosterFromBookings($coach);

        $todayBookings = Booking::query()
            ->forCoach($coach->id)
            ->confirmed()
            ->whereDate('session_date', $today)
            ->with(['athlete', 'location'])
            ->orderBy('session_time')
            ->get();

        $weekCount = Booking::query()
            ->forCoach($coach->id)
            ->confirmed()
            ->inWeek($weekStart)
            ->count();

        $weekHours = round(
            Booking::query()
                ->forCoach($coach->id)
                ->confirmed()
                ->inWeek($weekStart)
                ->get(['duration_minutes'])
                ->sum(fn (Booking $b) => ($b->duration_minutes ?? 60) / 60),
            1
        );

        return view('coach.dashboard', [
            'coach' => $coach,
            'players' => $players->take(6)->values()->all(),
            'today' => $todayBookings->map(fn (Booking $b) => [
                'time' => $b->session_time
                    ? Carbon::parse($b->session_time)->format('g:i A')
                    : '',
                'name' => $b->displayName(),
                'type' => $b->session_type ?? 'Session',
                'tone' => $b->tone(),
            ])->all(),
            'weekSessionCount' => $weekCount,
            'weekHours' => $weekHours,
            'todayLabel' => $today->format('l'),
        ]);
    }

    public function playerOverview(Request $request): View
    {
        $coach = $this->currentCoach();
        $allPlayers = $this->rosterFromBookings($coach)->values();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $roster = $allPlayers;

        if ($search !== '') {
            $needle = Str::lower($search);
            $roster = $roster->filter(function (array $player) use ($needle) {
                return str_contains(Str::lower($player['name'] ?? ''), $needle)
                    || str_contains(Str::lower($player['focus'] ?? ''), $needle);
            })->values();
        }

        if ($status === 'report-due') {
            $roster = $roster->where('reportDue', true)->values();
        } elseif ($status === 'on-track') {
            $roster = $roster->where('reportDue', false)->values();
        }

        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $players = new LengthAwarePaginator(
            $roster->forPage($page, $perPage)->values(),
            $roster->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('coach.player-overview', [
            'players' => $players,
            'totalPlayers' => $allPlayers->count(),
            'reportsDueTotal' => $allPlayers->where('reportDue', true)->count(),
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function playerShow(Request $request, string $player): View
    {
        $coach = $this->currentCoach();
        $profile = $this->resolvePlayerProfile($coach, $player);

        if (! $profile) {
            abort(404);
        }

        $history = $this->playerBookings($coach, $profile)->map(fn (Booking $b) => [
            'date' => $b->session_date?->format('M j, Y') ?? '',
            'type' => ($b->session_type ?? 'Session').' · '.($b->duration_minutes ?? 60).' min',
            'focus' => $b->session_type ?? 'General',
            'rating' => 0,
            'summary' => $b->location?->name
                ? 'At '.$b->location->name
                : 'Scheduled session',
        ])->all();

        $videos = SharedVideo::query()
            ->with('coach.user')
            ->where('coach_id', $coach->id)
            ->forPlayerProfile($profile)
            ->orderByDesc('created_at')
            ->get()
            ->map->toDisplayArray()
            ->all();

        return view('coach.player-show', [
            'player' => $profile,
            'skills' => [],
            'sessions' => $history,
            'goals' => [],
            'notes' => [],
            'videos' => $videos,
            'openVideosTab' => $request->query('tab') === 'videos' || session('open_videos_tab', false),
        ]);
    }

    public function storeVideo(Request $request, string $player): RedirectResponse
    {
        $coach = $this->currentCoach();
        $profile = $this->resolvePlayerProfile($coach, $player);

        if (! $profile) {
            abort(404);
        }

        $maxKb = (int) config('coachnow.max_video_upload_kb', 102400);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'source_type' => ['required', Rule::in(['url', 'upload'])],
            'url' => ['nullable', 'required_if:source_type,url', 'url', 'max:500'],
            'video' => [
                'nullable',
                'required_if:source_type,upload',
                'file',
                'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-matroska',
                'max:'.$maxKb,
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'duration_label' => ['nullable', 'string', 'max:40'],
            'skill_tag' => ['nullable', 'string', 'max:80'],
        ]);

        $payload = [
            'coach_id' => $coach->id,
            'athlete_id' => $profile['athlete_id'] ?? null,
            'athlete_name' => $profile['name'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_label' => $data['duration_label'] ?? null,
            'skill_tag' => $data['skill_tag'] ?? null,
            'source' => $data['source_type'],
            'url' => null,
            'file_path' => null,
            'thumbnail_path' => null,
            'disk' => null,
            'original_bytes' => null,
            'stored_bytes' => null,
            'is_compressed' => false,
        ];

        $status = 'Video shared with '.$profile['name'].'.';

        if ($data['source_type'] === 'upload') {
            $compressor = app(VideoCompressionService::class);
            $stored = $compressor->storeCompressed($request->file('video'));

            $payload['file_path'] = $stored['path'];
            $payload['thumbnail_path'] = $stored['thumbnail_path'] ?? null;
            $payload['disk'] = $stored['disk'];
            $payload['original_bytes'] = $stored['original_bytes'];
            $payload['stored_bytes'] = $stored['stored_bytes'];
            $payload['is_compressed'] = $stored['is_compressed'];

            $status = $stored['is_compressed']
                ? 'Video uploaded, compressed, and shared with '.$profile['name'].'.'
                : 'Video uploaded and shared with '.$profile['name'].'. Compression skipped (FFmpeg not found).';
        } else {
            $payload['url'] = $data['url'];
        }

        SharedVideo::query()->create($payload);

        return redirect()
            ->route('coach.players.show', ['player' => $profile['slug'], 'tab' => 'videos'])
            ->with('status', $status)
            ->with('open_videos_tab', true);
    }

    public function destroyVideo(string $player, SharedVideo $video): RedirectResponse
    {
        $coach = $this->currentCoach();
        $profile = $this->resolvePlayerProfile($coach, $player);

        if (! $profile || $video->coach_id !== $coach->id) {
            abort(404);
        }

        $belongsToPlayer = (! empty($profile['athlete_id']) && (int) $video->athlete_id === (int) $profile['athlete_id'])
            || (
                empty($video->athlete_id)
                && Str::lower((string) $video->athlete_name) === Str::lower((string) ($profile['name'] ?? ''))
            );

        if (! $belongsToPlayer) {
            abort(404);
        }

        app(VideoCompressionService::class)->deleteStored($video->file_path, $video->disk);
        app(VideoCompressionService::class)->deleteStored($video->thumbnail_path, $video->disk);
        $video->delete();

        return redirect()
            ->route('coach.players.show', ['player' => $profile['slug'], 'tab' => 'videos'])
            ->with('status', 'Video removed.')
            ->with('open_videos_tab', true);
    }

    public function addReport(Request $request): View
    {
        $coach = $this->currentCoach();
        $roster = $this->rosterFromBookings($coach)->values();
        $requestedSlug = trim((string) $request->query('player', ''));
        $profile = $requestedSlug !== ''
            ? $this->resolvePlayerProfile($coach, $requestedSlug)
            : null;
        $profile = $profile ?: $roster->first();

        return view('coach.add-report', [
            'player' => $profile ?: [
                'slug' => null,
                'name' => 'Select a player',
                'age' => '—',
                'sport' => '—',
            ],
            'roster' => $roster->all(),
        ]);
    }

    public function profile(): View
    {
        $coach = $this->currentCoach()->load('location');

        return view('coach.profile', [
            'coach' => $coach,
            'locations' => Location::query()->where('status', 'live')->orderBy('name')->get(['id', 'name', 'area']),
            'specialties' => Coach::SPECIALTIES,
            'experienceOptions' => Coach::EXPERIENCE_OPTIONS,
            'ageOptions' => Coach::AGE_OPTIONS,
            'missingFields' => $coach->missingProfileFields(),
            'isComplete' => $coach->isProfileComplete(),
        ]);
    }

    public function status(): JsonResponse
    {
        $coach = $this->currentCoach()->fresh();

        return response()->json([
            'coach_id' => $coach->id,
            'status' => $coach->status,
            'label' => ucfirst((string) $coach->status),
            'title' => match ($coach->status) {
                'active' => 'You’re live on Find a Coach',
                'paused' => 'Your listing was paused',
                'pending' => 'Your listing is pending review',
                default => 'Listing status updated',
            },
            'message' => match ($coach->status) {
                'active' => 'An admin approved your profile. Athletes can now discover and book you.',
                'paused' => 'An admin paused your profile. You’re hidden from Find a Coach until reactivated.',
                'pending' => 'Complete your profile, then wait for admin approval to go live.',
                default => 'Your CoachNow listing status is now '.$coach->status.'.',
            },
            'profile_url' => route('coach.profile'),
            'is_complete' => $coach->isReadyForListing(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $coach = $this->currentCoach();

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', Rule::in(Coach::SPECIALTIES)],
            'sport' => ['required', 'string', Rule::in(User::SPORTS)],
            'experience' => ['required', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'ages' => ['required', 'string', 'max:80'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'rate' => ['required', 'numeric', 'min:0', 'max:9999'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo') && ! $request->hasFile('photo')) {
            $coach->deleteStoredPhoto();
            $data['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            $coach->deleteStoredPhoto();
            $data['photo_path'] = $request->file('photo')->store('coach-photos', 'public');
        }

        unset($data['photo'], $data['remove_photo']);

        $coach->update($data);
        $coach->user?->update(['sport' => $data['sport']]);
        $coach->refresh();

        $message = $coach->isProfileComplete()
            ? ($coach->status === 'active'
                ? 'Profile updated. Your Find a Coach card is live.'
                : 'Profile saved. An admin will review and approve your listing.')
            : 'Profile saved. Finish the remaining fields so an admin can approve you.';

        return redirect()
            ->route('coach.profile')
            ->with('success', $message);
    }

    private function currentCoach(): Coach
    {
        $coach = auth()->user()?->coach;

        if (! $coach) {
            abort(403, 'Coach profile required.');
        }

        return $coach;
    }

    private function resolveWeekStart(?string $week): Carbon
    {
        if ($week) {
            try {
                return Carbon::parse($week)->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable) {
                // fall through
            }
        }

        return Carbon::today()->startOfWeek(Carbon::MONDAY);
    }

    private function calendarDays(Carbon $weekStart): array
    {
        $today = Carbon::today()->toDateString();

        return collect(range(0, 6))->map(function (int $offset) use ($weekStart, $today) {
            $day = $weekStart->copy()->addDays($offset);

            return [
                'name' => strtoupper($day->format('D')),
                'num' => $day->format('j'),
                'today' => $day->toDateString() === $today,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function mapBookingsToCalendar(Collection $bookings, Carbon $weekStart): array
    {
        $rows = (self::CAL_END_HOUR - self::CAL_START_HOUR) * 2;

        return $bookings->map(function (Booking $booking) use ($weekStart, $rows) {
            $sessionDay = $booking->session_date?->copy()->startOfDay();
            $weekDay = $weekStart->copy()->startOfDay();
            $dayIndex = $sessionDay
                ? (int) $weekDay->diffInDays($sessionDay, false)
                : 0;
            $dayIndex = max(0, min(6, $dayIndex));
            $time = $booking->session_time
                ? Carbon::parse($booking->session_time)->format('G:i')
                : '9:00';
            $duration = (int) ($booking->duration_minutes ?? 60);

            [$hours, $minutes] = array_map('intval', explode(':', $time));
            $offset = ($hours * 60 + $minutes) - (self::CAL_START_HOUR * 60);
            $start = max(1, (int) floor($offset / 30) + 1);
            $span = max(2, (int) ceil($duration / 30)); // at least 1 hour tall for readable labels

            $timeLabel = $booking->session_time
                ? Carbon::parse($booking->session_time)->format('g:i A')
                : '';

            return [
                'day' => $dayIndex,
                'start' => $time,
                'time_label' => $timeLabel,
                'duration' => $duration,
                'title' => $booking->displayName(),
                'type' => $booking->session_type ?? 'Session',
                'players' => 1,
                'tone' => $booking->tone(),
                'location' => $booking->location?->name,
                'date_label' => $booking->session_date?->format('D, M j') ?? '',
                'gridStart' => $start,
                'gridEnd' => min($rows + 1, $start + $span),
            ];
        })->values()->all();
    }

    private function preferWeekWithSessions(Coach $coach, Carbon $weekStart): Carbon
    {
        $thisWeekCount = Booking::query()
            ->forCoach($coach->id)
            ->confirmed()
            ->inWeek($weekStart)
            ->count();

        if ($thisWeekCount > 0) {
            return $weekStart;
        }

        $nearest = Booking::query()
            ->forCoach($coach->id)
            ->confirmed()
            ->upcoming()
            ->first();

        if (! $nearest) {
            $nearest = Booking::query()
                ->forCoach($coach->id)
                ->confirmed()
                ->orderByDesc('session_date')
                ->orderByDesc('session_time')
                ->first();
        }

        if (! $nearest?->session_date) {
            return $weekStart;
        }

        return $nearest->session_date->copy()->startOfWeek(Carbon::MONDAY);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rosterFromBookings(Coach $coach): Collection
    {
        $bookings = Booking::query()
            ->forCoach($coach->id)
            ->where('status', '!=', 'cancelled')
            ->with(['athlete', 'location'])
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get();

        return $bookings
            ->groupBy(fn (Booking $b) => $b->athlete_id ? 'u:'.$b->athlete_id : 'n:'.Str::lower($b->displayName()))
            ->map(function (Collection $group) use ($coach) {
                /** @var Booking $first */
                $first = $group->sortByDesc(fn (Booking $b) => $b->session_date?->timestamp ?? 0)->first();
                $name = $first->displayName();
                $slug = $first->athleteSlug();
                $upcoming = $group
                    ->filter(fn (Booking $b) => $b->isUpcoming())
                    ->sortBy(fn (Booking $b) => ($b->session_date?->timestamp ?? 0).' '.($b->session_time ?? ''))
                    ->first();
                $latestType = $first->session_type ?? 'General';

                $parts = preg_split('/\s+/', trim($name)) ?: [];
                $initials = collect($parts)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

                return [
                    'slug' => $slug,
                    'athlete_id' => $first->athlete_id,
                    'name' => $name,
                    'initials' => $initials ?: 'PL',
                    'age' => '—',
                    'sport' => $first->athlete?->sport ?: '—',
                    'sessions' => $group->count(),
                    'focus' => $latestType,
                    'next' => $upcoming?->whenLabel() ?? 'No upcoming',
                    'reportDue' => false,
                    'member_since' => $group->min('created_at')?->format('M Y') ?? '',
                    'plan' => 'Development Plus',
                    'plan_status' => 'Active',
                    'plan_renews' => '',
                    'total_sessions' => $group->count(),
                    'streak' => '—',
                    'progress' => 'Active',
                    'last_session' => $group->sortByDesc(fn (Booking $b) => $b->session_date?->timestamp ?? 0)->first()?->session_date?->format('M j, Y') ?? '',
                    'current_focus' => 'Continue working on '.strtolower($latestType).'.',
                    'next_session' => $upcoming
                        ? $upcoming->whenLabel().($upcoming->location?->name ? ' · '.$upcoming->location->name : '')
                        : 'No upcoming session',
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolvePlayerProfile(Coach $coach, string $slug): ?array
    {
        if ($slug === '') {
            return null;
        }

        return $this->rosterFromBookings($coach)->first(function (array $player) use ($slug) {
            return $player['slug'] === $slug
                || (string) ($player['athlete_id'] ?? '') === $slug
                || Str::slug($player['name']) === $slug;
        });
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, Booking>
     */
    private function playerBookings(Coach $coach, array $profile): Collection
    {
        $query = Booking::query()
            ->forCoach($coach->id)
            ->where('status', '!=', 'cancelled')
            ->with('location')
            ->orderByDesc('session_date')
            ->orderByDesc('session_time');

        if (! empty($profile['athlete_id'])) {
            $query->where('athlete_id', $profile['athlete_id']);
        } else {
            $query->whereNull('athlete_id')->where('athlete_name', $profile['name']);
        }

        return $query->limit(20)->get();
    }
}
