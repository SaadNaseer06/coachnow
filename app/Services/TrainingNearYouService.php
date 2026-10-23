<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\SessionRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrainingNearYouService
{
    /**
     * @param  array{lat:float,lng:float}|null  $origin
     * @return list<array<string, mixed>>
     */
    public function openGroupSessions(
        ?array $origin,
        int $radiusMiles = 5,
        ?string $sport = null,
        ?string $age = null,
        int $days = 7,
        int $limit = 40,
    ): array {
        $from = now()->startOfDay();
        $to = now()->addDays(max(1, $days))->endOfDay();

        $groups = CoachGroupSession::query()
            ->with(['coach.user', 'location'])
            ->where('is_public', true)
            ->whereIn('status', ['open', 'full'])
            ->whereDate('session_date', '>=', $from->toDateString())
            ->whereDate('session_date', '<=', $to->toDateString())
            ->whereHas('coach', fn ($q) => $q->where('status', 'active'))
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get();

        return $this->mapAndFilterGroups($groups, $origin, $radiusMiles, $sport, $age, $limit);
    }

    /**
     * Public marketplace session requests only (not coach-targeted).
     *
     * @param  array{lat:float,lng:float}|null  $origin
     * @return list<array<string, mixed>>
     */
    public function openSessionRequests(
        ?array $origin,
        int $radiusMiles = 5,
        ?string $sport = null,
        ?string $age = null,
        int $days = 7,
        int $limit = 40,
    ): array {
        $from = now()->startOfDay();
        $to = now()->addDays(max(1, $days))->endOfDay();

        $requests = SessionRequest::query()
            ->with(['location', 'requester', 'players'])
            ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
            ->whereNull('requested_coach_id')
            ->whereDoesntHave('invitedCoaches')
            ->where(function ($q) use ($from, $to) {
                $q->whereNull('session_date')
                    ->orWhere(function ($dated) use ($from, $to) {
                        $dated->whereDate('session_date', '>=', $from->toDateString())
                            ->whereDate('session_date', '<=', $to->toDateString());
                    });
            })
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->limit(80)
            ->get();

        // Hide requests that are full, past the 1-hour join cutoff, or whose
        // "accept by" deadline passed without a coach.
        $requests = $requests->reject(fn (SessionRequest $r) => $r->isJoinClosed()
            || $r->isUnhostedAndExpired()
            || ($r->max_players !== null && $r->players->count() >= (int) $r->max_players));

        return $this->mapAndFilterRequests($requests, $origin, $radiusMiles, $sport, $age, $limit);
    }

    /**
     * Featured cards for homepage (nearest / soonest public opportunities).
     *
     * @param  array{lat:float,lng:float}|null  $origin
     * @return list<array<string, mixed>>
     */
    public function featured(?array $origin = null, int $limit = 4): array
    {
        $merge = function (?array $originPoint, int $radius) use ($limit): array {
            $groups = $this->openGroupSessions($originPoint, $radius, null, null, 7, $limit);
            $requests = $this->openSessionRequests($originPoint, $radius, null, null, 7, $limit);

            return collect($groups)
                ->map(fn (array $row) => array_merge($row, ['kind' => 'group']))
                ->concat(collect($requests)->map(fn (array $row) => array_merge($row, ['kind' => 'request'])))
                ->sortBy(fn (array $row) => ($row['distance_miles'] ?? 9999).'|'.($row['sort_key'] ?? ''))
                ->take($limit)
                ->values()
                ->all();
        };

        $rows = $merge($origin, $origin ? 20 : 999);

        // Keep the homepage useful when geo returns nothing in-radius.
        if ($origin && $rows === []) {
            $rows = $merge(null, 999);
        }

        return $rows;
    }

    /**
     * Open marketplace session requests a coach can accept near their primary park.
     *
     * @return list<array<string, mixed>>
     */
    public function coachOpportunities(Coach $coach, int $radiusMiles = 20, int $limit = 5): array
    {
        $coach->loadMissing('location');
        $origin = null;
        if ($coach->location?->hasCoordinates()) {
            $origin = [
                'lat' => (float) $coach->location->latitude,
                'lng' => (float) $coach->location->longitude,
            ];
        }

        $from = now()->startOfDay();
        $to = now()->addDays(14)->endOfDay();

        $requests = SessionRequest::query()
            ->with(['location', 'requester', 'players'])
            ->where('status', 'open')
            ->whereNull('requested_coach_id')
            ->whereDoesntHave('invitedCoaches')
            ->where(function ($q) use ($from, $to) {
                $q->whereNull('session_date')
                    ->orWhere(function ($dated) use ($from, $to) {
                        $dated->whereDate('session_date', '>=', $from->toDateString())
                            ->whereDate('session_date', '<=', $to->toDateString());
                    });
            })
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->limit(80)
            ->get();

        $rows = $this->mapAndFilterRequests(
            $requests,
            $origin,
            $origin ? $radiusMiles : 999,
            null,
            null,
            $limit
        );

        // If nothing is within radius, still surface soonest open marketplace requests.
        if ($origin && $rows === []) {
            $rows = $this->mapAndFilterRequests($requests, null, 999, null, null, $limit);
        }

        return collect($rows)
            ->map(fn (array $row) => array_merge($row, [
                'kind' => 'request',
                'view_url' => route('coach.dashboard', ['requests' => 'open']),
                'view_label' => 'Review & accept',
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, CoachGroupSession>  $groups
     * @param  array{lat:float,lng:float}|null  $origin
     * @return list<array<string, mixed>>
     */
    private function mapAndFilterGroups(
        Collection $groups,
        ?array $origin,
        int $radiusMiles,
        ?string $sport,
        ?string $age,
        int $limit,
    ): array {
        $sport = $sport !== null && $sport !== '' ? strtolower($sport) : null;
        $age = $age !== null && $age !== '' ? $age : null;

        $rows = $groups->map(function (CoachGroupSession $group) use ($origin) {
            $coach = $group->coach;
            $location = $group->location;
            $distance = ($origin && $location)
                ? $location->distanceMilesFrom($origin['lat'], $origin['lng'])
                : null;
            $meta = $group->toParentSlotArray();
            $sports = $coach?->sportsList() ?? [];
            $price = $group->price_per_player !== null
                ? (float) $group->price_per_player
                : (float) ($coach?->rate ?? 0);

            if ($group->is_public) {
                $group->ensureShareToken();
            }

            return [
                'id' => $group->id,
                'kind' => 'group',
                'title' => $group->session_type ?: 'Group training',
                'coach_name' => $coach?->display_name ?? 'Coach',
                'coach_token' => $coach?->publicToken(),
                // The group's own sport; older groups without one fall back to the coach's sports.
                'sport' => $group->sportLabel(),
                'sports' => $group->sport ? [$group->sport] : $sports,
                'age_range' => $group->age_range,
                'location_name' => $location?->name ?? 'Field TBD',
                'location_area' => $location?->area,
                'date' => $group->session_date?->toDateString(),
                'date_label' => $group->session_date?->format('D, M j'),
                'time_label' => $group->timeLabel(),
                'price' => $price,
                'price_label' => '$'.number_format($price, 0).' / player',
                'booked_count' => $meta['booked_count'],
                'max_players' => $meta['max_players'],
                'spots_remaining' => $meta['spots_remaining'],
                'full' => $meta['full'],
                'joinable' => $meta['joinable'],
                'capacity_label' => $meta['capacity_label'],
                'distance_miles' => $distance,
                'share_url' => $group->is_public ? $group->shareUrl() : null,
                'book_url' => $coach
                    ? route('book-coach', ['coach' => $coach->publicToken(), 'group' => $group->id])
                    : null,
                'sort_key' => ($group->session_date?->toDateString() ?? '9999').'|'.substr((string) $group->session_time, 0, 5),
            ];
        });

        return $this->applyCommonFilters($rows, $origin, $radiusMiles, $sport, $age, $limit);
    }

    /**
     * @param  Collection<int, SessionRequest>  $requests
     * @param  array{lat:float,lng:float}|null  $origin
     * @return list<array<string, mixed>>
     */
    private function mapAndFilterRequests(
        Collection $requests,
        ?array $origin,
        int $radiusMiles,
        ?string $sport,
        ?string $age,
        int $limit,
    ): array {
        $sport = $sport !== null && $sport !== '' ? strtolower($sport) : null;
        $age = $age !== null && $age !== '' ? $age : null;

        $rows = $requests->map(function (SessionRequest $session) use ($origin) {
            $location = $session->location;
            $distance = ($origin && $location)
                ? $location->distanceMilesFrom($origin['lat'], $origin['lng'])
                : null;
            $players = $session->relationLoaded('players') ? $session->players->count() : $session->players()->count();
            $looking = $session->looking_for ?? max(0, (int) ($session->max_players ?? 0) - $players);

            return [
                'id' => $session->id,
                'kind' => 'request',
                'reference' => $session->reference,
                'title' => $session->session_type ?: 'Open session request',
                'coach_name' => null,
                'sport' => $session->sport ?: null,
                'sports' => $session->sport ? [$session->sport] : [],
                'age_range' => $session->age_range,
                'location_name' => $location?->name ?? ($session->location_name ?: 'Park TBD'),
                'location_area' => $location?->area ?? $session->location_city,
                'date' => $session->session_date?->toDateString(),
                'date_label' => $session->session_date?->format('D, M j') ?? 'Flexible date',
                'time_label' => $session->session_time
                    ? Carbon::parse($session->session_time)->format('g:i A')
                    : 'Time TBD',
                'price' => null,
                'price_label' => $session->price_range ?: 'Budget flexible',
                'booked_count' => $players,
                'max_players' => $session->max_players,
                'spots_remaining' => $looking,
                'full' => $session->max_players !== null && $players >= (int) $session->max_players,
                'joinable' => false,
                'capacity_label' => $players.' joined'.($looking > 0 ? ' · looking for '.$looking : ''),
                'distance_miles' => $distance,
                'share_url' => null,
                'book_url' => route('request-session'),
                'view_url' => $this->requestViewUrl($session),
                'view_label' => auth()->user()?->isCoach()
                    ? ($session->status === 'open' ? 'View & accept' : 'View request')
                    : 'View & join',
                'status_label' => $session->status === 'open' ? 'Waiting for a coach' : 'Coach confirmed',
                'sort_key' => ($session->session_date?->toDateString() ?? '9999').'|'.substr((string) ($session->session_time ?? '99:99'), 0, 5),
            ];
        });

        return $this->applyCommonFilters($rows, $origin, $radiusMiles, $sport, $age, $limit);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{lat:float,lng:float}|null  $origin
     * @return list<array<string, mixed>>
     */
    private function applyCommonFilters(
        Collection $rows,
        ?array $origin,
        int $radiusMiles,
        ?string $sport,
        ?string $age,
        int $limit,
    ): array {
        if ($sport) {
            $rows = $rows->filter(function (array $row) use ($sport) {
                $sports = array_map('strtolower', $row['sports'] ?? []);
                $primary = strtolower((string) ($row['sport'] ?? ''));

                return $primary === $sport || in_array($sport, $sports, true);
            })->values();
        }

        if ($age) {
            $rows = $rows->filter(function (array $row) use ($age) {
                $range = strtolower((string) ($row['age_range'] ?? ''));
                if ($range === '') {
                    return true;
                }

                return str_contains($range, strtolower($age))
                    || $this->ageRangesOverlap($range, $age);
            })->values();
        }

        if ($origin) {
            $rows = $rows
                ->filter(fn (array $row) => $row['distance_miles'] !== null && $row['distance_miles'] <= $radiusMiles)
                ->sortBy([
                    ['distance_miles', 'asc'],
                    ['sort_key', 'asc'],
                ])
                ->values();
        } else {
            $rows = $rows->sortBy('sort_key')->values();
        }

        return $rows->take($limit)->values()->all();
    }

    /**
     * Coaches go to their request inbox (to accept); everyone else to the public
     * request page to join. Coach-targeted requests never reach here.
     */
    private function requestViewUrl(SessionRequest $session): string
    {
        $user = auth()->user();

        if ($user?->isCoach()) {
            return route('coach.dashboard', ['requests' => 'open']);
        }

        return route('open-session-request', $session->reference);
    }

    /**
     * Numeric overlap between ranges like "Ages 10-16", "11-13" or "U12".
     */
    private function ageRangesOverlap(string $haystack, string $needle): bool
    {
        if (str_contains($haystack, 'all') || str_contains(strtolower($needle), 'all')) {
            return true;
        }

        $a = $this->parseAgeRange($haystack);
        $b = $this->parseAgeRange($needle);
        if (! $a || ! $b) {
            return false;
        }

        return $a[0] <= $b[1] && $b[0] <= $a[1];
    }

    /**
     * @return array{0:int,1:int}|null
     */
    private function parseAgeRange(string $value): ?array
    {
        if (preg_match('/(\d{1,2})\s*(?:-|–|to)\s*(\d{1,2})/i', $value, $m)) {
            return [min((int) $m[1], (int) $m[2]), max((int) $m[1], (int) $m[2])];
        }

        if (preg_match('/u\s*(\d{1,2})/i', $value, $m)) {
            return [0, (int) $m[1]];
        }

        if (preg_match('/(\d{1,2})\s*\+/', $value, $m)) {
            return [(int) $m[1], 99];
        }

        if (preg_match('/(\d{1,2})/', $value, $m)) {
            return [(int) $m[1], (int) $m[1]];
        }

        return null;
    }
}
