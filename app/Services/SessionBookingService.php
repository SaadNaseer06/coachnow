<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\CoachTimeBlock;
use App\Models\GroupJoinRequest;
use App\Models\SessionRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SessionBookingService
{
    public function createFromAcceptedSession(SessionRequest $session, Coach $coach): void
    {
        $session->loadMissing(['players', 'requester']);

        $amount = $session->deposit_amount !== null
            ? (float) $session->deposit_amount
            : (float) ($coach->rate ?? 0);

        $players = $session->players;
        if ($players->isEmpty() && $session->requester_id) {
            $players = collect([(object) [
                'user_id' => $session->requester_id,
                'name' => $session->requester?->name ?? 'Player',
            ]]);
        }

        foreach ($players as $player) {
            $athleteId = $player->user_id ?? null;
            $athleteName = $player->name ?? 'Player';

            $existing = Booking::query()
                ->where('session_request_id', $session->id)
                ->when(
                    $athleteId,
                    fn ($q) => $q->where('athlete_id', $athleteId),
                    fn ($q) => $q->whereNull('athlete_id')->where('athlete_name', $athleteName)
                )
                ->first();

            $payload = [
                'coach_id' => $coach->id,
                'athlete_id' => $athleteId,
                'location_id' => $session->location_id ?? $coach->location_id,
                'athlete_name' => $athleteName,
                'session_type' => $session->session_type ?? 'Private',
                'session_date' => $session->session_date?->toDateString(),
                'session_time' => $session->session_time,
                'duration_minutes' => 60,
                'amount' => $amount,
                'status' => 'confirmed',
                'session_request_id' => $session->id,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Booking::query()->create(array_merge($payload, [
                    'reference' => Booking::generateReference(),
                ]));
            }
        }
    }

    /**
     * @param  array{
     *   date: string,
     *   time: string,
     *   location_id: int,
     *   duration_minutes?: int,
     *   session_type?: string,
     *   group_session_id?: int|null
     * }  $slot
     */
    public function createDirectBooking(Coach $coach, User $athlete, array $slot): Booking
    {
        $date = $slot['date'];
        $time = substr((string) $slot['time'], 0, 5);
        $duration = max(30, (int) ($slot['duration_minutes'] ?? 60));
        $locationId = (int) $slot['location_id'];
        $groupId = ! empty($slot['group_session_id']) ? (int) $slot['group_session_id'] : null;

        if ($groupId) {
            throw ValidationException::withMessages([
                'slot' => 'Use Request to Join for group sessions.',
            ]);
        }

        $open = app(CoachAvailabilityService::class)
            ->openSlotsForCoach($coach, Carbon::parse($date), Carbon::parse($date), $duration);

        $match = collect($open)->first(function (array $row) use ($date, $time, $locationId) {
            return $row['date'] === $date
                && $row['time'] === $time
                && (int) $row['location_id'] === $locationId
                && empty($row['group_session_id']);
        });

        if (! $match) {
            throw ValidationException::withMessages([
                'slot' => 'That time is no longer available. Pick another slot.',
            ]);
        }

        return Booking::query()->create([
            'reference' => Booking::generateReference(),
            'session_request_id' => null,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'location_id' => $locationId,
            'athlete_name' => $athlete->name,
            'session_type' => $slot['session_type'] ?? 'Private 1-on-1',
            'session_date' => $date,
            'session_time' => $time,
            'duration_minutes' => (int) ($match['duration_minutes'] ?? $duration),
            'amount' => (float) ($coach->rate ?? 0),
            'status' => 'confirmed',
        ]);
    }

    /**
     * Parent requests to join an open group session (coach must accept).
     */
    public function requestJoinGroupSession(Coach $coach, User $athlete, int $groupSessionId, ?string $notes = null): GroupJoinRequest
    {
        $group = CoachGroupSession::query()
            ->where('coach_id', $coach->id)
            ->whereKey($groupSessionId)
            ->firstOrFail();

        if (! $group->isJoinable()) {
            throw ValidationException::withMessages([
                'slot' => 'That group session is full or no longer open.',
            ]);
        }

        $alreadyBooked = $group->activeBookings()
            ->where('athlete_id', $athlete->id)
            ->exists();

        if ($alreadyBooked) {
            throw ValidationException::withMessages([
                'slot' => 'You’re already booked on this group session.',
            ]);
        }

        $existing = GroupJoinRequest::query()
            ->where('group_session_id', $group->id)
            ->where('athlete_id', $athlete->id)
            ->first();

        if ($existing && $existing->status === 'pending') {
            return $existing;
        }

        if ($existing && $existing->status === 'accepted') {
            throw ValidationException::withMessages([
                'slot' => 'You’re already booked on this group session.',
            ]);
        }

        return GroupJoinRequest::query()->updateOrCreate(
            [
                'group_session_id' => $group->id,
                'athlete_id' => $athlete->id,
            ],
            [
                'athlete_name' => $athlete->name,
                'notes' => $notes,
                'status' => 'pending',
            ]
        );
    }

    /**
     * @param  array{
     *   athlete_id?: int|null,
     *   player_name?: string|null,
     *   date: string,
     *   time: string,
     *   location_id: int,
     *   duration_minutes?: int,
     *   session_type?: string,
     *   amount?: float|null,
     *   notes?: string|null,
     *   max_players?: int|null,
     *   force_double_book?: bool
     * }  $data
     */
    public function createCoachBooking(Coach $coach, array $data): Booking
    {
        $athleteId = ! empty($data['athlete_id']) ? (int) $data['athlete_id'] : null;
        $playerName = trim((string) ($data['player_name'] ?? ''));
        $date = $data['date'];
        $time = substr((string) $data['time'], 0, 5);
        $duration = max(30, (int) ($data['duration_minutes'] ?? 60));
        $locationId = (int) $data['location_id'];
        $sessionType = (string) ($data['session_type'] ?? 'Private 1-on-1');
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;
        $force = ! empty($data['force_double_book']);
        $isGroup = CoachGroupSession::isGroupType($sessionType);
        $maxPlayers = $isGroup
            ? max(2, (int) ($data['max_players'] ?? 4))
            : null;

        $athlete = null;
        if ($athleteId) {
            $athlete = User::query()
                ->whereKey($athleteId)
                ->where('role', User::ROLE_ATHLETE)
                ->first();

            if (! $athlete) {
                throw ValidationException::withMessages([
                    'athlete_id' => 'That player account was not found.',
                ]);
            }

            $playerName = $athlete->name;
        }

        if ($playerName === '') {
            throw ValidationException::withMessages([
                'player_name' => 'Choose a roster player or enter a client name.',
            ]);
        }

        $this->assertAthleteAndPersonalClear($athleteId, $date, $time, $duration, $coach->id);

        if (! $force && $this->coachHasExclusiveOverlap($coach->id, $date, $time, $duration)) {
            throw ValidationException::withMessages([
                'time' => 'This session overlaps with an existing booking. Would you like to continue and double-book this time?',
                'double_book' => '1',
            ]);
        }

        $amount = array_key_exists('amount', $data) && $data['amount'] !== null
            ? (float) $data['amount']
            : (float) ($coach->rate ?? 0);

        return DB::transaction(function () use ($coach, $athlete, $playerName, $date, $time, $duration, $locationId, $sessionType, $notes, $isGroup, $maxPlayers, $amount) {
            $groupId = null;
            if ($isGroup) {
                $group = CoachGroupSession::query()->create([
                    'coach_id' => $coach->id,
                    'location_id' => $locationId,
                    'session_type' => $sessionType,
                    'session_date' => $date,
                    'session_time' => $time,
                    'duration_minutes' => $duration,
                    'max_players' => $maxPlayers,
                    'notes' => $notes,
                    'status' => 'open',
                ]);
                $groupId = $group->id;
            }

            $booking = Booking::query()->create([
                'reference' => Booking::generateReference(),
                'session_request_id' => null,
                'coach_id' => $coach->id,
                'athlete_id' => $athlete?->id,
                'location_id' => $locationId,
                'athlete_name' => $playerName,
                'session_type' => $sessionType,
                'session_date' => $date,
                'session_time' => $time,
                'duration_minutes' => $duration,
                'amount' => $amount,
                'status' => 'confirmed',
                'notes' => $notes,
                'max_players' => $maxPlayers,
                'group_session_id' => $groupId,
            ]);

            if ($groupId) {
                CoachGroupSession::query()->whereKey($groupId)->first()?->syncStatusFromRoster();
            }

            return $booking;
        });
    }

    /**
     * @param  array{
     *   session_date?: string,
     *   session_time?: string,
     *   duration_minutes?: int,
     *   location_id?: int,
     *   session_type?: string,
     *   notes?: string|null,
     *   max_players?: int|null,
     *   athlete_id?: int|null,
     *   player_name?: string|null,
     *   force_double_book?: bool,
     *   add_players?: list<array{athlete_id?:int|null,player_name?:string|null}>
     * }  $data
     */
    public function updateCoachBooking(Coach $coach, Booking $booking, array $data): Booking
    {
        if ((int) $booking->coach_id !== (int) $coach->id) {
            abort(403);
        }

        $group = $booking->group_session_id
            ? CoachGroupSession::query()->whereKey($booking->group_session_id)->first()
            : null;

        $date = $data['session_date'] ?? $booking->session_date?->toDateString();
        $time = substr((string) ($data['session_time'] ?? $booking->session_time), 0, 5);
        $duration = max(30, (int) ($data['duration_minutes'] ?? $booking->duration_minutes ?? 60));
        $locationId = (int) ($data['location_id'] ?? $booking->location_id);
        $sessionType = (string) ($data['session_type'] ?? $booking->session_type);
        $notes = array_key_exists('notes', $data) ? trim((string) ($data['notes'] ?? '')) : $booking->notes;
        $force = ! empty($data['force_double_book']);
        $isGroup = CoachGroupSession::isGroupType($sessionType) || $group !== null;
        $maxPlayers = $isGroup
            ? max(2, (int) ($data['max_players'] ?? $group?->max_players ?? $booking->max_players ?? 4))
            : null;

        $ignoreBookingIds = $group
            ? $group->bookings()->pluck('id')->all()
            : [$booking->id];
        $ignoreGroupId = $group?->id;

        $athleteId = array_key_exists('athlete_id', $data)
            ? (! empty($data['athlete_id']) ? (int) $data['athlete_id'] : null)
            : $booking->athlete_id;
        $playerName = array_key_exists('player_name', $data)
            ? trim((string) ($data['player_name'] ?? ''))
            : (string) $booking->athlete_name;

        if ($athleteId) {
            $athlete = User::query()->whereKey($athleteId)->where('role', User::ROLE_ATHLETE)->first();
            if ($athlete) {
                $playerName = $athlete->name;
            }
        }

        $this->assertAthleteAndPersonalClear(
            $athleteId ? (int) $athleteId : 0,
            $date,
            $time,
            $duration,
            $coach->id,
            null
        );

        if (! $force && $this->coachHasExclusiveOverlap($coach->id, $date, $time, $duration, $ignoreBookingIds, $ignoreGroupId)) {
            throw ValidationException::withMessages([
                'time' => 'This session overlaps with an existing booking. Would you like to continue and double-book this time?',
                'double_book' => '1',
            ]);
        }

        return DB::transaction(function () use (
            $booking, $group, $date, $time, $duration, $locationId, $sessionType,
            $notes, $isGroup, $maxPlayers, $athleteId, $playerName, $data, $coach
        ) {
            if ($isGroup) {
                if (! $group) {
                    $group = CoachGroupSession::query()->create([
                        'coach_id' => $coach->id,
                        'location_id' => $locationId,
                        'session_type' => $sessionType,
                        'session_date' => $date,
                        'session_time' => $time,
                        'duration_minutes' => $duration,
                        'max_players' => $maxPlayers,
                        'notes' => $notes,
                        'status' => 'open',
                    ]);
                    $booking->group_session_id = $group->id;
                } else {
                    $group->update([
                        'location_id' => $locationId,
                        'session_type' => $sessionType,
                        'session_date' => $date,
                        'session_time' => $time,
                        'duration_minutes' => $duration,
                        'max_players' => $maxPlayers,
                        'notes' => $notes,
                    ]);
                    $group->activeBookings()->update([
                        'session_date' => $date,
                        'session_time' => $time,
                        'duration_minutes' => $duration,
                        'location_id' => $locationId,
                        'session_type' => $sessionType,
                        'max_players' => $maxPlayers,
                        'notes' => $notes,
                    ]);
                }

                foreach ($data['add_players'] ?? [] as $player) {
                    $this->addPlayerToGroup($coach, $group->fresh(), $player, true);
                }

                $group->fresh()?->syncStatusFromRoster();
            }

            $booking->fill([
                'athlete_id' => $athleteId,
                'athlete_name' => $playerName !== '' ? $playerName : $booking->athlete_name,
                'session_date' => $date,
                'session_time' => $time,
                'duration_minutes' => $duration,
                'location_id' => $locationId,
                'session_type' => $sessionType,
                'notes' => $notes,
                'max_players' => $maxPlayers,
                'group_session_id' => $isGroup ? ($group?->id ?? $booking->group_session_id) : null,
            ])->save();

            return $booking->fresh(['athlete', 'location', 'groupSession']);
        });
    }

    /**
     * @param  array{athlete_id?:int|null,player_name?:string|null}  $player
     */
    public function addPlayerToGroup(Coach $coach, CoachGroupSession $group, array $player, bool $force = false): Booking
    {
        if ((int) $group->coach_id !== (int) $coach->id) {
            abort(403);
        }

        if (! $group->isJoinable() && ! $force) {
            throw ValidationException::withMessages([
                'player_name' => 'This group session is full.',
            ]);
        }

        $athleteId = ! empty($player['athlete_id']) ? (int) $player['athlete_id'] : null;
        $playerName = trim((string) ($player['player_name'] ?? ''));
        $athlete = null;

        if ($athleteId) {
            $athlete = User::query()->whereKey($athleteId)->where('role', User::ROLE_ATHLETE)->first();
            if ($athlete) {
                $playerName = $athlete->name;
            }
        }

        if ($playerName === '') {
            throw ValidationException::withMessages([
                'player_name' => 'Enter a player name.',
            ]);
        }

        $duplicate = $group->activeBookings()
            ->when($athleteId, fn ($q) => $q->where('athlete_id', $athleteId))
            ->when(! $athleteId, fn ($q) => $q->where('athlete_name', $playerName))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'player_name' => 'That player is already on this session.',
            ]);
        }

        $booking = Booking::query()->create([
            'reference' => Booking::generateReference(),
            'coach_id' => $coach->id,
            'athlete_id' => $athlete?->id,
            'location_id' => $group->location_id,
            'athlete_name' => $playerName,
            'session_type' => $group->session_type,
            'session_date' => $group->session_date?->toDateString(),
            'session_time' => $group->session_time,
            'duration_minutes' => $group->durationMinutes(),
            'amount' => (float) ($coach->rate ?? 0),
            'status' => 'confirmed',
            'notes' => $group->notes,
            'max_players' => $group->max_players,
            'group_session_id' => $group->id,
        ]);

        $group->syncStatusFromRoster();

        return $booking;
    }

    public function removePlayerFromGroup(Coach $coach, Booking $booking): void
    {
        if ((int) $booking->coach_id !== (int) $coach->id || ! $booking->group_session_id) {
            abort(403);
        }

        $group = $booking->groupSession;
        $booking->update(['status' => 'cancelled']);
        $group?->syncStatusFromRoster();
    }

    public function moveCoachBooking(
        Coach $coach,
        Booking $booking,
        string $date,
        string $time,
        bool $forceDoubleBook = false,
    ): Booking {
        return $this->updateCoachBooking($coach, $booking, [
            'session_date' => $date,
            'session_time' => substr($time, 0, 5),
            'force_double_book' => $forceDoubleBook,
        ]);
    }

    public function cancelCoachBooking(Coach $coach, Booking $booking): void
    {
        if ((int) $booking->coach_id !== (int) $coach->id) {
            abort(403);
        }

        DB::transaction(function () use ($booking) {
            if ($booking->group_session_id) {
                $group = $booking->groupSession;
                if ($group) {
                    $group->activeBookings()->update(['status' => 'cancelled']);
                    $group->update(['status' => 'cancelled']);
                    $group->joinRequests()->where('status', 'pending')->update(['status' => 'declined']);
                }
            } else {
                $booking->update(['status' => 'cancelled']);
            }
        });
    }

    public function acceptGroupJoinRequest(Coach $coach, GroupJoinRequest $join): Booking
    {
        $group = $join->groupSession;
        if (! $group || (int) $group->coach_id !== (int) $coach->id) {
            abort(403);
        }

        if ($join->status !== 'pending') {
            throw ValidationException::withMessages([
                'join' => 'That join request is no longer pending.',
            ]);
        }

        if (! $group->isJoinable()) {
            throw ValidationException::withMessages([
                'join' => 'This group session is full.',
            ]);
        }

        return DB::transaction(function () use ($coach, $group, $join) {
            $booking = $this->addPlayerToGroup($coach, $group, [
                'athlete_id' => $join->athlete_id,
                'player_name' => $join->displayName(),
            ], true);

            $join->update(['status' => 'accepted']);

            return $booking;
        });
    }

    public function declineGroupJoinRequest(Coach $coach, GroupJoinRequest $join): void
    {
        $group = $join->groupSession;
        if (! $group || (int) $group->coach_id !== (int) $coach->id) {
            abort(403);
        }

        $join->update(['status' => 'declined']);
    }

    public function syncMissingForCoach(Coach $coach): int
    {
        $sessions = SessionRequest::query()
            ->where('host_coach_id', $coach->id)
            ->whereIn('status', ['hosted', 'awaiting_deposit', 'confirmed'])
            ->whereDoesntHave('bookings')
            ->with(['players', 'requester'])
            ->get();

        foreach ($sessions as $session) {
            $this->createFromAcceptedSession($session, $coach);
        }

        return $sessions->count();
    }

    private function assertAthleteAndPersonalClear(
        ?int $athleteId,
        string $date,
        string $time,
        int $duration,
        int $coachId,
        ?int $ignoreRequestId = null,
    ): void {
        if ($athleteId) {
            $conflict = SessionRequest::schedulingConflictMessage(
                $athleteId,
                $date,
                $time,
                $coachId,
                $ignoreRequestId,
                $duration,
                'coach'
            );
            if ($conflict) {
                $lower = strtolower($conflict);
                $isAthleteConflict = str_contains($lower, 'player')
                    || str_contains($lower, 'already have a');
                if ($isAthleteConflict) {
                    throw ValidationException::withMessages(['time' => $conflict]);
                }
            }
        }

        if (SessionRequest::coachHasPersonalBlockAt($coachId, $date, $time, $duration)) {
            throw ValidationException::withMessages([
                'time' => 'That time overlaps personal time on your calendar. Pick another slot.',
            ]);
        }
    }

    /**
     * @param  list<int>  $ignoreBookingIds
     */
    public function coachHasExclusiveOverlap(
        int $coachId,
        string $date,
        string $time,
        int $duration,
        array $ignoreBookingIds = [],
        ?int $ignoreGroupId = null,
    ): bool {
        $bookings = Booking::query()
            ->where('coach_id', $coachId)
            ->whereDate('session_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreBookingIds !== [], fn ($q) => $q->whereNotIn('id', $ignoreBookingIds))
            ->get(['id', 'session_time', 'duration_minutes', 'group_session_id']);

        foreach ($bookings as $booking) {
            if ($ignoreGroupId && (int) $booking->group_session_id === (int) $ignoreGroupId) {
                continue;
            }

            // Joinable group sessions don't count as exclusive conflicts for double-book warnings
            // when moving onto their slot — coach double-book still warns on any overlap.
            if (! SessionRequest::timesOverlap(
                $time,
                $duration,
                $booking->session_time,
                (int) ($booking->duration_minutes ?: 60)
            )) {
                continue;
            }

            return true;
        }

        return false;
    }
}
