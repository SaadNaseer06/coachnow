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

        // Each family pays deposit + one convenience fee (its own transaction).
        $deposit = $session->deposit_amount !== null
            ? (float) $session->deposit_amount
            : (float) ($coach->rate ?? 0);
        $fee = $deposit > 0 ? (float) config('coachnow.payments.convenience_fee', 2) : 0.0;

        $players = $session->players;
        if ($players->isEmpty() && $session->requester_id) {
            $players = collect([(object) [
                'user_id' => $session->requester_id,
                'name' => $session->requester?->name ?? 'Player',
            ]]);
        }

        $group = $this->privateGroupForAcceptedSession($session, $coach, $players->count());

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
                'amount' => $deposit + $fee,
                'platform_fee_amount' => $fee,
                'coach_payout_amount' => $deposit,
                'payment_status' => ($player->paid ?? false) ? 'paid' : 'unpaid',
                'stripe_payment_intent_id' => $player->stripe_payment_intent_id ?? null,
                'status' => 'confirmed',
                'session_request_id' => $session->id,
                'group_session_id' => $group?->id,
                'max_players' => $group?->max_players,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Booking::query()->create(array_merge($payload, [
                    'reference' => Booking::generateReference(),
                ]));
            }
        }

        $group?->syncStatusFromRoster();
    }

    /**
     * Group requests become a private group session on the coach's calendar: the
     * booked places are reserved, and the coach can later open spare spots to the public.
     */
    private function privateGroupForAcceptedSession(SessionRequest $session, Coach $coach, int $playerCount): ?CoachGroupSession
    {
        $maxPlayers = (int) ($session->max_players ?? 0);
        $isGroup = CoachGroupSession::isGroupType($session->session_type) || $maxPlayers > 1 || $playerCount > 1;
        if (! $isGroup || ! $session->session_date || ! $session->session_time) {
            return null;
        }

        $existingId = Booking::query()
            ->where('session_request_id', $session->id)
            ->whereNotNull('group_session_id')
            ->value('group_session_id');

        if ($existingId && ($existing = CoachGroupSession::query()->find($existingId))) {
            return $existing;
        }

        return CoachGroupSession::query()->create([
            'coach_id' => $coach->id,
            'location_id' => $session->location_id ?? $coach->location_id,
            'session_type' => $session->session_type ?: 'Group training',
            'session_date' => $session->session_date->toDateString(),
            'session_time' => $session->session_time,
            'duration_minutes' => SessionRequest::defaultDurationMinutes(),
            'max_players' => max(2, $maxPlayers, $playerCount),
            'status' => 'open',
            'is_public' => false,
            'age_range' => $session->age_range,
            'sport' => $session->sport,
            'price_per_player' => (float) ($coach->rate ?? 0),
        ]);
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

        $match = $this->matchOpenSlot($coach, $slot);

        $sessionAmount = (float) ($coach->rate ?? 0);
        $convenienceFee = (float) config('coachnow.payments.convenience_fee', 2);

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
            'amount' => $sessionAmount + $convenienceFee,
            'platform_fee_amount' => $convenienceFee,
            'coach_payout_amount' => $sessionAmount,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Price for one parent checkout on a public group: per-player price × players,
     * plus a single convenience fee for the whole transaction.
     *
     * @return array{price_per_player: float, session_amount: float, convenience_fee: float, total: float}
     */
    public function groupCheckoutQuote(CoachGroupSession $group, int $playerCount): array
    {
        $price = $group->price_per_player !== null
            ? (float) $group->price_per_player
            : (float) ($group->coach?->rate ?? 0);
        $sessionAmount = round($price * max(1, $playerCount), 2);
        $fee = $sessionAmount > 0 ? (float) config('coachnow.payments.convenience_fee', 2) : 0.0;

        return [
            'price_per_player' => $price,
            'session_amount' => $sessionAmount,
            'convenience_fee' => $fee,
            'total' => round($sessionAmount + $fee, 2),
        ];
    }

    /**
     * @param  list<string>  $playerNames
     */
    public function assertGroupJoinable(Coach $coach, CoachGroupSession $group, User $athlete, array $playerNames): void
    {
        if ((int) $group->coach_id !== (int) $coach->id) {
            throw ValidationException::withMessages(['slot' => 'That group session belongs to a different coach.']);
        }

        if (! $group->is_public) {
            throw ValidationException::withMessages(['slot' => 'That group session is private and not open for joining.']);
        }

        if (! $group->isJoinable()) {
            throw ValidationException::withMessages(['slot' => 'That group session is full or no longer open.']);
        }

        $count = count($playerNames);
        if ($count < 1) {
            throw ValidationException::withMessages(['players' => 'Add at least one player.']);
        }

        $spots = $group->spotsRemaining();
        if ($count > $spots) {
            throw ValidationException::withMessages([
                'players' => "Only {$spots} spot".($spots === 1 ? '' : 's').' left in this group session.',
            ]);
        }

        $existing = $group->activeBookings()
            ->where(fn ($q) => $q->where('athlete_id', $athlete->id)->orWhereIn('athlete_name', $playerNames))
            ->pluck('athlete_name')
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->all();

        foreach ($playerNames as $name) {
            if (in_array(strtolower($name), $existing, true)) {
                throw ValidationException::withMessages(['players' => "{$name} is already booked on this group session."]);
            }
        }
    }

    /**
     * Parent books one or more players onto a public group session in one transaction.
     * The convenience fee is recorded once, on the first player's booking.
     *
     * @param  list<string>  $playerNames
     * @return list<Booking>
     */
    public function joinGroupSession(Coach $coach, User $athlete, int $groupSessionId, array $playerNames, ?string $paymentIntentId = null): array
    {
        $playerNames = array_values(array_unique(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $playerNames
        ))));

        return DB::transaction(function () use ($coach, $athlete, $groupSessionId, $playerNames, $paymentIntentId) {
            $group = CoachGroupSession::query()
                ->with('coach')
                ->whereKey($groupSessionId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertGroupJoinable($coach, $group, $athlete, $playerNames);

            return $this->createGroupBookings($coach, $athlete, $group, $playerNames, $paymentIntentId);
        });
    }

    /**
     * Quote for a parent booking a new private group on an open Book Now slot.
     *
     * @return array{price_per_player: float, session_amount: float, convenience_fee: float, total: float}
     */
    public function newGroupCheckoutQuote(Coach $coach, int $playerCount): array
    {
        $price = (float) ($coach->group_rate ?? 0);
        $sessionAmount = round($price * max(1, $playerCount), 2);
        $fee = $sessionAmount > 0 ? (float) config('coachnow.payments.convenience_fee', 2) : 0.0;

        return [
            'price_per_player' => $price,
            'session_amount' => $sessionAmount,
            'convenience_fee' => $fee,
            'total' => round($sessionAmount + $fee, 2),
        ];
    }

    /**
     * @param  list<string>  $playerNames
     * @return array<string, mixed> The open slot being booked.
     */
    public function assertNewGroupBookable(Coach $coach, array $slot, array $playerNames): array
    {
        if (! $coach->offersGroupBooking()) {
            throw ValidationException::withMessages(['slot' => 'This coach doesn’t offer group bookings yet.']);
        }

        $count = count($playerNames);
        if ($count < 2) {
            throw ValidationException::withMessages(['players' => 'A group booking needs at least 2 players.']);
        }

        if ($count > (int) $coach->max_group_size) {
            throw ValidationException::withMessages([
                'players' => 'This coach takes up to '.(int) $coach->max_group_size.' players per group.',
            ]);
        }

        return $this->matchOpenSlot($coach, $slot);
    }

    /**
     * Parent books an open slot for 2+ players. The group starts private — the places
     * are reserved and nothing is advertised until the coach opens spare spots.
     *
     * @param  array{date: string, time: string, location_id: int, duration_minutes?: int}  $slot
     * @param  list<string>  $playerNames
     * @return list<Booking>
     */
    public function createPrivateGroupBooking(Coach $coach, User $athlete, array $slot, array $playerNames, ?string $paymentIntentId = null): array
    {
        $playerNames = array_values(array_unique(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $playerNames
        ))));

        $match = $this->assertNewGroupBookable($coach, $slot, $playerNames);

        return DB::transaction(function () use ($coach, $athlete, $match, $playerNames, $paymentIntentId) {
            $group = CoachGroupSession::query()->create([
                'coach_id' => $coach->id,
                'location_id' => (int) $match['location_id'],
                'session_type' => 'Group training',
                'sport' => $coach->sportsList()[0] ?? ($coach->sport ?: null),
                'session_date' => $match['date'],
                'session_time' => $match['time'],
                'duration_minutes' => (int) $match['duration_minutes'],
                'max_players' => max(count($playerNames), (int) $coach->max_group_size),
                'status' => 'open',
                'is_public' => false,
                'price_per_player' => (float) $coach->group_rate,
            ]);
            $group->setRelation('coach', $coach);

            return $this->createGroupBookings($coach, $athlete, $group, $playerNames, $paymentIntentId);
        });
    }

    /**
     * One booking per player; the convenience fee is recorded once, on the first booking.
     *
     * @param  list<string>  $playerNames
     * @return list<Booking>
     */
    private function createGroupBookings(Coach $coach, User $athlete, CoachGroupSession $group, array $playerNames, ?string $paymentIntentId): array
    {
        $quote = $this->groupCheckoutQuote($group, count($playerNames));
        $price = $quote['price_per_player'];
        $bookings = [];

        foreach ($playerNames as $index => $name) {
            $fee = $index === 0 ? $quote['convenience_fee'] : 0.0;
            $bookings[] = Booking::query()->create([
                'reference' => Booking::generateReference(),
                'coach_id' => $coach->id,
                'athlete_id' => $athlete->id,
                'location_id' => $group->location_id,
                'athlete_name' => $name,
                'session_type' => $group->session_type,
                'session_date' => $group->session_date?->toDateString(),
                'session_time' => $group->session_time,
                'duration_minutes' => $group->durationMinutes(),
                'amount' => $price + $fee,
                'platform_fee_amount' => $fee,
                'coach_payout_amount' => $price,
                'stripe_payment_intent_id' => $paymentIntentId,
                'payment_status' => $paymentIntentId ? 'paid' : 'unpaid',
                'status' => 'confirmed',
                'notes' => $group->notes,
                'max_players' => $group->max_players,
                'group_session_id' => $group->id,
            ]);
        }

        $group->syncStatusFromRoster();

        return $bookings;
    }

    /**
     * @param  array{date: string, time: string, location_id: int, duration_minutes?: int}  $slot
     * @return array<string, mixed>
     */
    private function matchOpenSlot(Coach $coach, array $slot): array
    {
        $date = Carbon::parse($slot['date'])->toDateString();
        $time = substr((string) $slot['time'], 0, 5);
        $duration = max(30, (int) ($slot['duration_minutes'] ?? 60));
        $locationId = (int) $slot['location_id'];

        $open = app(CoachAvailabilityService::class)
            ->openSlotsForCoach($coach, Carbon::parse($date), Carbon::parse($date), $duration);

        $match = collect($open)->first(fn (array $row) => $row['date'] === $date
            && $row['time'] === $time
            && (int) $row['location_id'] === $locationId
            && empty($row['group_session_id']));

        if (! $match) {
            throw ValidationException::withMessages([
                'slot' => 'That time is no longer available. Pick another slot.',
            ]);
        }

        return $match;
    }

    /**
     * Parent requests to join an open group session (coach must accept).
     * Legacy flow — new joins book instantly via joinGroupSession().
     */
    public function requestJoinGroupSession(Coach $coach, User $athlete, int $groupSessionId, ?string $notes = null): GroupJoinRequest
    {
        $group = CoachGroupSession::query()
            ->where('coach_id', $coach->id)
            ->whereKey($groupSessionId)
            ->firstOrFail();

        if (! $group->is_public) {
            throw ValidationException::withMessages([
                'slot' => 'That group session is private and not open for joining.',
            ]);
        }

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
        $ageRange = $isGroup ? ($data['age_range'] ?? null) : null;
        $sport = $isGroup ? ($data['sport'] ?? null) ?: ($coach->sportsList()[0] ?? null) : null;
        $pricePerPlayer = $isGroup && array_key_exists('price_per_player', $data) && $data['price_per_player'] !== null && $data['price_per_player'] !== ''
            ? (float) $data['price_per_player']
            : ($isGroup ? (float) ($coach->rate ?? 0) : null);
        $isPublic = $isGroup ? (bool) ($data['is_public'] ?? false) : false;

        if ($isPublic && blank($ageRange)) {
            throw ValidationException::withMessages([
                'age_range' => 'Set an age range before opening this group to the public.',
            ]);
        }

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
            : (float) ($pricePerPlayer ?? $coach->rate ?? 0);

        return DB::transaction(function () use ($coach, $athlete, $playerName, $date, $time, $duration, $locationId, $sessionType, $notes, $isGroup, $maxPlayers, $amount, $ageRange, $pricePerPlayer, $isPublic, $sport) {
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
                    'is_public' => $isPublic,
                    'age_range' => $ageRange,
                    'sport' => $sport,
                    'price_per_player' => $pricePerPlayer,
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
        $sport = $isGroup
            ? (array_key_exists('sport', $data) ? ($data['sport'] ?: null) : $group?->sport)
            : null;
        $ageRange = $isGroup
            ? (array_key_exists('age_range', $data) ? ($data['age_range'] ?: null) : $group?->age_range)
            : null;
        $pricePerPlayer = $isGroup
            ? (array_key_exists('price_per_player', $data)
                ? ($data['price_per_player'] !== null && $data['price_per_player'] !== '' ? (float) $data['price_per_player'] : null)
                : ($group?->price_per_player !== null ? (float) $group->price_per_player : (float) ($coach->rate ?? 0)))
            : null;
        $isPublic = $isGroup
            ? (array_key_exists('is_public', $data) ? (bool) $data['is_public'] : (bool) ($group?->is_public ?? false))
            : false;

        if ($isPublic && blank($ageRange)) {
            throw ValidationException::withMessages([
                'age_range' => 'Set an age range before opening this group to the public.',
            ]);
        }

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
            null,
            $ignoreBookingIds
        );

        if (! $force && $this->coachHasExclusiveOverlap($coach->id, $date, $time, $duration, $ignoreBookingIds, $ignoreGroupId)) {
            throw ValidationException::withMessages([
                'time' => 'This session overlaps with an existing booking. Would you like to continue and double-book this time?',
                'double_book' => '1',
            ]);
        }

        return DB::transaction(function () use (
            $booking, $group, $date, $time, $duration, $locationId, $sessionType,
            $notes, $isGroup, $maxPlayers, $athleteId, $playerName, $data, $coach,
            $ageRange, $pricePerPlayer, $isPublic, $sport
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
                        'is_public' => $isPublic,
                        'age_range' => $ageRange,
                        'sport' => $sport,
                        'price_per_player' => $pricePerPlayer,
                    ]);
                    $booking->group_session_id = $group->id;
                } else {
                    $group->fill([
                        'location_id' => $locationId,
                        'session_type' => $sessionType,
                        'session_date' => $date,
                        'session_time' => $time,
                        'duration_minutes' => $duration,
                        'max_players' => $maxPlayers,
                        'notes' => $notes,
                        'is_public' => $isPublic,
                        'age_range' => $ageRange,
                        'sport' => $sport,
                        'price_per_player' => $pricePerPlayer,
                    ]);
                    if ($isPublic) {
                        $group->ensureShareToken();
                    }
                    $group->save();
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

    /**
     * Parent cancels one player's booking. Refunds the player's session price when it
     * was paid by card; the convenience fee stays. Frees the group spot if any.
     *
     * @return float Amount refunded.
     */
    public function cancelByAthlete(User $athlete, Booking $booking): float
    {
        if ($reason = $booking->athleteCancelBlockReason($athlete)) {
            throw ValidationException::withMessages(['booking' => $reason]);
        }

        $refundCents = 0;
        $stripe = app(StripeService::class);
        if ($booking->stripe_payment_intent_id && $booking->payment_status === 'paid' && $stripe->enabled()) {
            $refundCents = (int) round(((float) $booking->coach_payout_amount) * 100);
            $stripe->refundPaymentIntent($booking->stripe_payment_intent_id, $refundCents, [
                'booking_reference' => (string) $booking->reference,
                'reason' => 'parent_cancelled',
            ]);
        }

        DB::transaction(function () use ($booking, $refundCents) {
            $booking->forceFill([
                'status' => 'cancelled',
                'payment_status' => $refundCents > 0 ? 'partially_refunded' : $booking->payment_status,
            ])->save();

            $booking->groupSession?->syncStatusFromRoster();
        });

        return $refundCents / 100;
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
        array $ignoreBookingIds = [],
    ): void {
        if ($athleteId) {
            $conflict = SessionRequest::schedulingConflictMessage(
                $athleteId,
                $date,
                $time,
                $coachId,
                $ignoreRequestId,
                $duration,
                'coach',
                $ignoreBookingIds
            );
            if ($conflict) {
                $lower = strtolower($conflict);
                // Only the player's own clashes block here. "You already have a session…"
                // is the coach's calendar, which the double-book prompt handles.
                $isAthleteConflict = str_contains($lower, 'player');
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
