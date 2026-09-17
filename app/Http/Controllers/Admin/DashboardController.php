<?php

namespace App\Http\Controllers\Admin;

use App\Events\CoachStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\ContactMessage;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\User;
use App\Services\AppMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeCoaches = Coach::query()->where('status', 'active')->count();
        $bookingsToday = Booking::query()->whereDate('session_date', Carbon::today())->count();
        $parkCount = Location::query()->count();
        $revenueMtd = (float) Booking::query()
            ->where('status', '!=', 'cancelled')
            ->whereMonth('session_date', Carbon::now()->month)
            ->whereYear('session_date', Carbon::now()->year)
            ->sum('amount');

        $recentBookings = Booking::query()
            ->with(['coach', 'location'])
            ->orderByDesc('session_date')
            ->orderByDesc('session_time')
            ->limit(6)
            ->get();

        $topLocations = Location::query()
            ->withCount('coaches')
            ->orderByDesc('coaches_count')
            ->orderBy('distance_miles')
            ->limit(4)
            ->get();

        $pendingCoaches = Coach::query()
            ->with(['user', 'location'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'activeCoaches' => $activeCoaches,
            'bookingsToday' => $bookingsToday,
            'parkCount' => $parkCount,
            'revenueMtd' => $revenueMtd,
            'recentBookings' => $recentBookings,
            'topLocations' => $topLocations,
            'pendingCoaches' => $pendingCoaches,
        ]);
    }

    public function coaches(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $locationId = $request->query('location_id');

        $coaches = Coach::query()
            ->with(['user', 'location'])
            ->withCount([
                'bookings as upcoming_bookings_count' => fn ($q) => $q->upcoming(),
                'requestedSessions as open_request_count' => fn ($q) => $q->where('status', 'open'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('display_name', 'like', "%{$search}%")
                        ->orWhere('specialty', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['pending', 'active', 'paused'], true), fn ($query) => $query->where('status', $status))
            ->when($locationId === 'none', fn ($query) => $query->whereNull('location_id'))
            ->when(is_numeric($locationId), fn ($query) => $query->where('location_id', (int) $locationId))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $coaches->getCollection()->transform(function (Coach $coach) {
            $coach->upcoming_commitments_count = (int) ($coach->upcoming_bookings_count ?? 0)
                + (int) ($coach->open_request_count ?? 0);

            return $coach;
        });

        $locations = Location::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.coaches', [
            'coaches' => $coaches,
            'locations' => $locations,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'location_id' => $locationId,
            ],
        ]);
    }

    public function storeCoach(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'sport' => ['required', 'string', Rule::in(User::SPORTS)],
            'specialty' => ['required', 'string', Rule::in(Coach::SPECIALTIES)],
            'ages' => ['nullable', 'string', 'max:80'],
            'rate' => ['required', 'numeric', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['pending', 'active', 'paused'])],
            'experience' => ['nullable', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'sport' => $data['sport'],
                'password' => $data['password'],
                'role' => User::ROLE_COACH,
            ]);

            $displayName = str_starts_with(strtolower($data['name']), 'coach ')
                ? $data['name']
                : 'Coach '.Str::of($data['name'])->before(' ')->toString();

            Coach::query()->create([
                'user_id' => $user->id,
                'location_id' => $data['location_id'],
                'display_name' => $displayName,
                'sport' => $data['sport'],
                'specialty' => $data['specialty'],
                'ages' => $data['ages'] ?? null,
                'experience' => $data['experience'] ?? null,
                'bio' => $data['bio'] ?? null,
                'rate' => $data['rate'],
                'status' => $data['status'],
                'rating' => 5.0,
                'reviews_count' => 0,
                'photo_path' => 'assets/Rectangle 8.png',
            ]);
        });

        return redirect()
            ->route('admin.coaches')
            ->with('success', 'Coach added successfully.');
    }

    public function updateCoach(Request $request, Coach $coach): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'sport' => ['required', 'string', Rule::in(User::SPORTS)],
            'specialty' => ['required', 'string', Rule::in(Coach::SPECIALTIES)],
            'ages' => ['nullable', 'string', 'max:80'],
            'rate' => ['required', 'numeric', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['pending', 'active', 'paused'])],
            'experience' => ['nullable', 'string', Rule::in(Coach::EXPERIENCE_OPTIONS)],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        $coach->update(collect($data)->except('status')->all());
        $coach->user?->update(['sport' => $data['sport']]);

        if ($data['status'] !== $coach->status && $data['status'] === 'paused') {
            return redirect()
                ->route('admin.coaches')
                ->withErrors([
                    'status' => 'Use Pause on the coach list to confirm and suspend upcoming sessions.',
                ]);
        }

        if ($data['status'] !== $coach->fresh()->status) {
            $previous = $coach->status;
            $coach->update(['status' => $data['status']]);
            $this->notifyCoachOfStatusChange($coach->fresh(['user']), $previous, $data['status']);
        }

        return redirect()
            ->route('admin.coaches')
            ->with('success', $coach->display_name.' was updated.');
    }

    public function updateCoachStatus(Request $request, Coach $coach): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'paused'])],
            'force' => ['sometimes', 'boolean'],
        ]);

        if ($data['status'] === 'active' && ! $coach->isReadyForListing()) {
            return redirect()
                ->route('admin.coaches')
                ->withErrors([
                    'status' => $coach->display_name.' is missing: '.implode(', ', $coach->missingListingFields()).'. Ask them to finish their profile first.',
                ]);
        }

        $upcomingCount = 0;
        $suspendedCount = 0;
        if ($data['status'] === 'paused') {
            $upcomingCount = $this->upcomingCommitmentCount($coach);

            if ($upcomingCount > 0 && ! $request->boolean('force')) {
                return redirect()
                    ->route('admin.coaches')
                    ->withErrors([
                        'status' => $coach->display_name.' has '.$upcomingCount.' upcoming session'.($upcomingCount === 1 ? '' : 's').'. Confirm pause to hide them from Find a Coach and suspend those sessions.',
                    ]);
            }

            if ($request->boolean('force') || $upcomingCount > 0) {
                $suspendedCount = $this->suspendUpcomingSessions($coach);
            }
        }

        $previous = $coach->status;
        $coach->update(['status' => $data['status']]);
        $this->notifyCoachOfStatusChange($coach->fresh(['user']), $previous, $data['status']);

        $message = match ($data['status']) {
            'active' => $coach->display_name.' is now live on Find a Coach.',
            'paused' => $coach->display_name.' was paused and hidden from Find a Coach.'
                .($suspendedCount > 0 ? ' '.$suspendedCount.' upcoming session'.($suspendedCount === 1 ? '' : 's').' were suspended.' : ''),
            default => $coach->display_name.' was set back to pending.',
        };

        return redirect()
            ->route('admin.coaches')
            ->with('success', $message);
    }

    public function bookings(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $range = (string) $request->query('range', '');

        $bookings = Booking::query()
            ->with(['coach', 'location', 'athlete'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference', 'like', "%{$search}%")
                        ->orWhere('athlete_name', 'like', "%{$search}%")
                        ->orWhere('session_type', 'like', "%{$search}%")
                        ->orWhereHas('coach', fn ($coach) => $coach->where('display_name', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn ($location) => $location->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('athlete', fn ($athlete) => $athlete->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['confirmed', 'pending', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($range === 'today', fn ($query) => $query->whereDate('session_date', Carbon::today()))
            ->when($range === 'week', function ($query) {
                $start = Carbon::today()->startOfWeek(Carbon::MONDAY);
                $query->whereBetween('session_date', [$start->toDateString(), $start->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()]);
            })
            ->when($range === 'month', function ($query) {
                $query->whereMonth('session_date', Carbon::now()->month)
                    ->whereYear('session_date', Carbon::now()->year);
            })
            ->orderByDesc('session_date')
            ->orderByDesc('session_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings', [
            'bookings' => $bookings,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'range' => $range,
            ],
        ]);
    }

    public function locations(): View
    {
        $locations = Location::query()
            ->with(['coaches' => fn ($q) => $q->orderBy('display_name')])
            ->withCount('coaches')
            ->orderBy('distance_miles')
            ->paginate(15)
            ->withQueryString();

        return view('admin.locations', [
            'locations' => $locations,
            'totalParks' => Location::query()->count(),
            'assignedCoaches' => Coach::query()->whereNotNull('location_id')->count(),
            'avgDistance' => round((float) (Location::query()->avg('distance_miles') ?? 0), 1),
        ]);
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'area' => ['required', 'string', 'max:120'],
            'distance_miles' => ['required', 'numeric', 'min:0', 'max:999'],
            'status' => ['required', Rule::in(['live', 'draft'])],
        ]);

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $i = 2;
        while (Location::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i;
            $i++;
        }

        Location::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'area' => $data['area'],
            'distance_miles' => $data['distance_miles'],
            'status' => $data['status'],
            'image_path' => 'assets/Background.png',
        ]);

        return redirect()
            ->route('admin.locations')
            ->with('success', 'Location added successfully.');
    }

    public function destroyLocation(Location $location): RedirectResponse
    {
        $name = $location->name;
        $location->delete();

        return redirect()
            ->route('admin.locations')
            ->with('success', "“{$name}” was deleted. Assigned coaches were unlinked from this park.");
    }

    public function athletes(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $activity = (string) $request->query('activity', '');

        $athletes = User::query()
            ->where('role', User::ROLE_ATHLETE)
            ->withCount('athleteBookings')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($activity === 'new', fn ($query) => $query->having('athlete_bookings_count', '<=', 3))
            ->when($activity === 'month', function ($query) {
                $query->whereHas('athleteBookings', function ($bookings) {
                    $bookings->whereMonth('session_date', Carbon::now()->month)
                        ->whereYear('session_date', Carbon::now()->year);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $athletes->getCollection()->transform(function (User $athlete) {
            $last = Booking::query()
                ->with('location')
                ->where('athlete_id', $athlete->id)
                ->orderByDesc('session_date')
                ->first();

            $athlete->setAttribute('sessions_count', $athlete->athlete_bookings_count);
            $athlete->setAttribute('last_booking', $last);
            $athlete->setAttribute('preferred_park', $last?->location?->name);

            return $athlete;
        });

        return view('admin.athletes', [
            'athletes' => $athletes,
            'filters' => [
                'q' => $search,
                'activity' => $activity,
            ],
        ]);
    }

    public function messages(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $messages = ContactMessage::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.messages', [
            'messages' => $messages,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function markMessageRead(ContactMessage $message): RedirectResponse
    {
        if ($message->read_at === null) {
            $message->forceFill(['read_at' => now()])->save();
        }

        return back()->with('success', 'Message marked as read.');
    }

    public function schedule(\Illuminate\Http\Request $request): View
    {
        $weekStart = $this->resolveWeekStart($request->query('week'));
        $today = Carbon::today()->toDateString();
        $isCurrentWeek = $weekStart->toDateString() === Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        $bookings = Booking::query()
            ->confirmed()
            ->inWeek($weekStart)
            ->with(['athlete', 'coach.user', 'location'])
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get();

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $today) {
            $day = $weekStart->copy()->addDays($offset);

            return [
                'label' => strtoupper($day->format('D')),
                'date' => $day->format('j'),
                'full' => $day->format('D, M j'),
                'iso' => $day->toDateString(),
                'today' => $day->toDateString() === $today,
            ];
        })->all();

        $sessions = $bookings->map(function (Booking $booking) use ($weekStart) {
            $dayIndex = max(0, min(6, (int) $weekStart->diffInDays($booking->session_date->copy()->startOfDay(), false)));
            $startCarbon = $booking->session_time
                ? Carbon::parse($booking->session_date->toDateString().' '.$booking->session_time)
                : $booking->session_date->copy()->setTime(9, 0);
            $duration = (int) ($booking->duration_minutes ?? 60);
            $endCarbon = $startCarbon->copy()->addMinutes($duration);
            $timeKey = $startCarbon->format('G:i');
            $grid = $this->timeToGrid($timeKey, $duration);
            $tone = $booking->tone();
            if ($tone === 'green') {
                $tone = 'blue';
            }

            return [
                'id' => $booking->id,
                'day' => $dayIndex,
                'start' => $timeKey,
                'start_minutes' => ($startCarbon->hour * 60) + $startCarbon->minute,
                'end_minutes' => ($endCarbon->hour * 60) + $endCarbon->minute,
                'duration' => $duration,
                'title' => $booking->displayName(),
                'type' => $booking->session_type ?? 'Session',
                'players' => 1,
                'tone' => $tone,
                'gridStart' => $grid['start'],
                'gridEnd' => $grid['end'],
                'time_label' => $startCarbon->format('g:i A').' – '.$endCarbon->format('g:i A'),
                'time_short' => $startCarbon->format('g:ia'),
                'coach' => $booking->coach?->display_name ?? 'Unassigned coach',
                'location' => $booking->location?->name ?? '—',
                'area' => $booking->location?->area ?? '',
                'status' => ucfirst($booking->status),
                'reference' => $booking->reference,
                'amount' => $booking->amount !== null ? '$'.number_format((float) $booking->amount, 0) : null,
                'date_label' => $booking->session_date?->format('l, M j') ?? '',
            ];
        })->values()->all();

        return view('admin.schedule', [
            'weekLabel' => $weekStart->format('M j').' – '.$weekStart->copy()->addDays(6)->format('M j, Y'),
            'weekStart' => $weekStart->toDateString(),
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'isCurrentWeek' => $isCurrentWeek,
            'sessionCount' => count($sessions),
            'days' => $days,
            'sessions' => $sessions,
        ]);
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

    private function upcomingCommitmentCount(Coach $coach): int
    {
        $bookings = Booking::query()
            ->forCoach($coach->id)
            ->upcoming()
            ->count();

        $openRequests = SessionRequest::query()
            ->where('requested_coach_id', $coach->id)
            ->where('status', 'open')
            ->count();

        return $bookings + $openRequests;
    }

    private function suspendUpcomingSessions(Coach $coach): int
    {
        $bookingCount = Booking::query()
            ->forCoach($coach->id)
            ->upcoming()
            ->update(['status' => 'cancelled']);

        $requestCount = SessionRequest::query()
            ->where(function ($q) use ($coach) {
                $q->where('requested_coach_id', $coach->id)
                    ->orWhere('host_coach_id', $coach->id);
            })
            ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
            ->where(function ($q) {
                $q->whereDate('session_date', '>', now()->toDateString())
                    ->orWhere(function ($inner) {
                        $inner->whereDate('session_date', now()->toDateString())
                            ->where(function ($time) {
                                $time->whereNull('session_time')
                                    ->orWhereTime('session_time', '>=', now()->format('H:i:s'));
                            });
                    });
            })
            ->update(['status' => 'cancelled']);

        return $bookingCount + $requestCount;
    }

    private function timeToGrid(string $time, int $duration): array
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        $startMinutes = ($hours * 60 + $minutes) - (4 * 60);
        $gridStart = max(1, (int) floor($startMinutes / 30) + 1);
        $gridSpan = max(1, (int) ceil($duration / 30));

        return ['start' => $gridStart, 'end' => $gridStart + $gridSpan];
    }

    private function notifyCoachOfStatusChange(Coach $coach, string $previousStatus, string $status): void
    {
        if ($previousStatus === $status) {
            return;
        }

        app(AppMailer::class)->notifyCoachStatusChanged($coach, $status);

        if (config('broadcasting.default') !== 'pusher' || ! config('broadcasting.connections.pusher.key')) {
            return;
        }

        try {
            CoachStatusChanged::dispatch($coach, $previousStatus, $status);
        } catch (\Throwable) {
            // Never fail the admin action if broadcasting is misconfigured.
        }
    }
}
