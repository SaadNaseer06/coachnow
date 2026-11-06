<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SessionRequest extends Model
{
    protected $fillable = [
        'reference',
        'requester_id',
        'location_id',
        'location_name',
        'location_city',
        'session_date',
        'session_time',
        'session_type',
        'sport',
        'age_range',
        'price_range',
        'player_level',
        'notes',
        'min_players',
        'max_players',
        'looking_for',
        'know_by_at',
        'deposit_amount',
        'price_per_player',
        'payment_note',
        'card_on_file',
        'status',
        'host_coach_id',
        'requested_coach_id',
        'coach_note',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'know_by_at' => 'datetime',
            'accepted_at' => 'datetime',
            'deposit_amount' => 'decimal:2',
            'price_per_player' => 'decimal:2',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'looking_for' => 'integer',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function hostCoach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'host_coach_id');
    }

    public function requestedCoach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'requested_coach_id');
    }

    public function invitedCoaches(): BelongsToMany
    {
        return $this->belongsToMany(Coach::class, 'session_request_coaches')
            ->withTimestamps();
    }

    public function players(): HasMany
    {
        return $this->hasMany(SessionRequestPlayer::class);
    }

    /**
     * Request was sent to specific coach(es), not the open marketplace.
     */
    public function isTargeted(): bool
    {
        if ($this->requested_coach_id !== null) {
            return true;
        }

        if ($this->relationLoaded('invitedCoaches')) {
            return $this->invitedCoaches->isNotEmpty();
        }

        return $this->invitedCoaches()->exists();
    }

    /**
     * @return list<int>
     */
    public function invitedCoachIds(): array
    {
        if ($this->relationLoaded('invitedCoaches')) {
            return $this->invitedCoaches->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->invitedCoaches()->pluck('coaches.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * True when this coach may see / accept the open request.
     */
    public function isVisibleToCoach(Coach $coach): bool
    {
        if ($this->host_coach_id === $coach->id) {
            return true;
        }

        if ($this->status !== 'open') {
            return $this->host_coach_id === $coach->id;
        }

        $invitedIds = $this->invitedCoachIds();
        if ($invitedIds !== []) {
            return in_array((int) $coach->id, $invitedIds, true);
        }

        if ($this->requested_coach_id === null) {
            return $coach->status === 'active';
        }

        return (int) $this->requested_coach_id === (int) $coach->id;
    }

    /**
     * Scope requests this coach is allowed to see in their inbox.
     */
    public function scopeVisibleToCoach($query, Coach $coach)
    {
        return $query->where(function ($q) use ($coach) {
            $q->where('host_coach_id', $coach->id)
                ->orWhere(function ($open) use ($coach) {
                    $open->where('status', 'open')
                        ->where(function ($target) use ($coach) {
                            $target->where('requested_coach_id', $coach->id)
                                ->orWhereHas('invitedCoaches', fn ($inv) => $inv->where('coaches.id', $coach->id))
                                ->orWhere(function ($openMarket) {
                                    $openMarket->whereNull('requested_coach_id')
                                        ->whereDoesntHave('invitedCoaches');
                                });
                        });
                });
        });
    }

    public static function defaultDurationMinutes(): int
    {
        return 60;
    }

    /**
     * True when two start times overlap given each session's length.
     */
    public static function timesOverlap(?string $startA, int $durationA, ?string $startB, int $durationB): bool
    {
        if (! $startA || ! $startB) {
            return false;
        }

        try {
            $a0 = Carbon::parse($startA);
            $b0 = Carbon::parse($startB);
        } catch (\Throwable) {
            return false;
        }

        $a1 = $a0->copy()->addMinutes(max(1, $durationA));
        $b1 = $b0->copy()->addMinutes(max(1, $durationB));

        return $a0->lt($b1) && $b0->lt($a1);
    }

    /**
     * Conflict if the athlete or target coach already has an overlapping active slot.
     */
    public static function hasSchedulingConflict(
        int $athleteId,
        string $sessionDate,
        ?string $sessionTime,
        ?int $coachId = null,
        ?int $ignoreRequestId = null,
        int $durationMinutes = 60,
    ): bool {
        return static::schedulingConflictMessage(
            $athleteId,
            $sessionDate,
            $sessionTime,
            $coachId,
            $ignoreRequestId,
            $durationMinutes,
        ) !== null;
    }

    public static function schedulingConflictMessage(
        int $athleteId,
        string $sessionDate,
        ?string $sessionTime,
        ?int $coachId = null,
        ?int $ignoreRequestId = null,
        int $durationMinutes = 60,
        string $audience = 'athlete',
        array $ignoreBookingIds = [],
    ): ?string {
        if ($sessionTime === null || $sessionTime === '') {
            return null;
        }

        $durationMinutes = $durationMinutes > 0 ? $durationMinutes : static::defaultDurationMinutes();
        $slotHint = 'Sessions last 60 minutes — pick a slot after the other one ends.';

        $athleteKind = static::athleteOverlapKind(
            $athleteId,
            $sessionDate,
            $sessionTime,
            $durationMinutes,
            $ignoreRequestId,
            $ignoreBookingIds
        );

        if ($coachId) {
            if ($athleteKind === 'request') {
                return $audience === 'coach'
                    ? 'This player already has a request that overlaps that time. '.$slotHint
                    : 'You already have a request that overlaps that time. Cancel it or pick a later slot. '.$slotHint;
            }
            if ($athleteKind === 'booking') {
                return $audience === 'coach'
                    ? 'This player already has a booked session that overlaps that time. '.$slotHint
                    : 'You already have a booked session that overlaps that time. '.$slotHint;
            }
            if (static::coachHasPersonalBlockAt($coachId, $sessionDate, $sessionTime, $durationMinutes)) {
                $name = Coach::query()->find($coachId)?->display_name ?: 'That coach';

                return $audience === 'coach'
                    ? 'That time overlaps personal time on your calendar. Decline this request or move the personal block.'
                    : $name.' is not available at that time (personal time blocked).';
            }
            if (static::coachIsBusyAt($coachId, $sessionDate, $sessionTime, $durationMinutes, $ignoreRequestId)) {
                $name = Coach::query()->find($coachId)?->display_name ?: 'That coach';

                return $audience === 'coach'
                    ? 'You already have a session that overlaps that time. '.$slotHint
                    : $name.' already has a session that overlaps that time. '.$slotHint;
            }

            return null;
        }

        $busyCoaches = static::busyActiveCoachNames(
            $sessionDate,
            $sessionTime,
            $durationMinutes,
            $ignoreRequestId
        );
        $activeCount = Coach::query()->where('status', 'active')->count();

        if ($activeCount > 0 && count($busyCoaches) >= $activeCount) {
            if (count($busyCoaches) === 1) {
                return $busyCoaches[0].' is the only available coach and already has a session at that time. '.$slotHint;
            }

            return 'No coaches are free at that time. '.$slotHint;
        }

        if ($athleteKind === 'request') {
            return 'You already have a request that overlaps that time. Cancel it or pick a later slot. '.$slotHint;
        }
        if ($athleteKind === 'booking') {
            return 'You already have a booked session that overlaps that time. '.$slotHint;
        }

        return null;
    }

    private static function athleteOverlapKind(
        int $athleteId,
        string $sessionDate,
        string $sessionTime,
        int $durationMinutes,
        ?int $ignoreRequestId,
        array $ignoreBookingIds = [],
    ): ?string {
        $activeStatuses = ['open', 'hosted', 'awaiting_deposit', 'confirmed'];

        $athleteRequests = static::query()
            ->where('requester_id', $athleteId)
            ->whereDate('session_date', $sessionDate)
            ->whereIn('status', $activeStatuses)
            ->when($ignoreRequestId, fn ($q) => $q->where('id', '!=', $ignoreRequestId))
            ->get(['session_time', 'host_coach_id']);

        foreach ($athleteRequests as $existing) {
            if (! static::timesOverlap($sessionTime, $durationMinutes, $existing->session_time, static::defaultDurationMinutes())) {
                continue;
            }

            return $existing->host_coach_id ? 'booking' : 'request';
        }

        $athleteBookings = Booking::query()
            ->where('athlete_id', $athleteId)
            ->whereDate('session_date', $sessionDate)
            ->where('status', '!=', 'cancelled')
            // The session being moved (and its own group roster) can't conflict with itself.
            ->when($ignoreBookingIds !== [], fn ($q) => $q->whereNotIn('id', $ignoreBookingIds))
            ->get(['session_time', 'duration_minutes']);

        foreach ($athleteBookings as $booking) {
            if (static::timesOverlap(
                $sessionTime,
                $durationMinutes,
                $booking->session_time,
                (int) ($booking->duration_minutes ?: static::defaultDurationMinutes())
            )) {
                return 'booking';
            }
        }

        return null;
    }

    private static function coachIsBusyAt(
        int $coachId,
        string $sessionDate,
        string $sessionTime,
        int $durationMinutes,
        ?int $ignoreRequestId,
    ): bool {
        $activeStatuses = ['open', 'hosted', 'awaiting_deposit', 'confirmed'];

        $coachRequests = static::query()
            ->where(function ($q) use ($coachId) {
                $q->where('requested_coach_id', $coachId)
                    ->orWhere('host_coach_id', $coachId);
            })
            ->whereDate('session_date', $sessionDate)
            ->whereIn('status', $activeStatuses)
            ->when($ignoreRequestId, fn ($q) => $q->where('id', '!=', $ignoreRequestId))
            ->get(['session_time']);

        foreach ($coachRequests as $existing) {
            if (static::timesOverlap($sessionTime, $durationMinutes, $existing->session_time, static::defaultDurationMinutes())) {
                return true;
            }
        }

        $coachBookings = Booking::query()
            ->where('coach_id', $coachId)
            ->whereDate('session_date', $sessionDate)
            ->where('status', '!=', 'cancelled')
            ->get(['session_time', 'duration_minutes']);

        foreach ($coachBookings as $booking) {
            if (static::timesOverlap(
                $sessionTime,
                $durationMinutes,
                $booking->session_time,
                (int) ($booking->duration_minutes ?: static::defaultDurationMinutes())
            )) {
                return true;
            }
        }

        if (static::coachHasPersonalBlockAt($coachId, $sessionDate, $sessionTime, $durationMinutes)) {
            return true;
        }

        return false;
    }

    public static function coachHasPersonalBlockAt(
        int $coachId,
        string $sessionDate,
        string $sessionTime,
        int $durationMinutes,
    ): bool {
        $blocks = CoachTimeBlock::query()
            ->where('coach_id', $coachId)
            ->whereDate('block_date', $sessionDate)
            ->get(['block_date', 'start_time', 'end_time']);

        foreach ($blocks as $block) {
            $blockStart = substr((string) $block->start_time, 0, 5);
            $blockMinutes = $block->durationMinutes();
            if (static::timesOverlap($sessionTime, $durationMinutes, $blockStart, $blockMinutes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function busyActiveCoachNames(
        string $sessionDate,
        string $sessionTime,
        int $durationMinutes,
        ?int $ignoreRequestId,
    ): array {
        $names = [];

        $coaches = Coach::query()
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name']);

        foreach ($coaches as $coach) {
            if (static::coachIsBusyAt((int) $coach->id, $sessionDate, $sessionTime, $durationMinutes, $ignoreRequestId)) {
                $names[] = $coach->display_name;
            }
        }

        return $names;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'CN-'.random_int(1000, 9999);
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function whenLabel(): string
    {
        $date = $this->session_date?->format('D, M j') ?? '';
        $time = $this->session_time
            ? Carbon::parse($this->session_time)->format('g:i A')
            : '';

        return collect([$date, $time])->filter()->implode(' · ');
    }

    /** Coaches can accept, and families can join, until this long before the session. */
    public const CUTOFF_MINUTES = 60;

    public function knowByLabel(): string
    {
        return $this->acceptDeadline()?->format('D, M j · g:i A') ?? '';
    }

    /**
     * The real "Accept by" time: the parent's chosen deadline, or — for "Anytime" —
     * one hour before the session. Never later than that one-hour cutoff.
     */
    public function acceptDeadline(): ?Carbon
    {
        $cutoff = $this->startsAt()?->copy()->subMinutes(self::CUTOFF_MINUTES);

        if ($this->know_by_at && $cutoff) {
            return $this->know_by_at->lt($cutoff) ? $this->know_by_at->copy() : $cutoff;
        }

        return $this->know_by_at?->copy() ?? $cutoff;
    }

    public function acceptSeconds(): int
    {
        $deadline = $this->acceptDeadline();
        if (! $deadline) {
            return 0;
        }

        return max(0, $deadline->getTimestamp() - now()->getTimestamp());
    }

    public function postedLabel(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Awaiting coach',
            'awaiting_deposit' => 'Accepted · Arrange payment',
            'hosted', 'confirmed' => 'Accepted',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'open' => 'amber',
            'hosted', 'awaiting_deposit', 'confirmed' => 'green',
            'cancelled' => 'zinc',
            'expired' => 'red',
            default => 'zinc',
        };
    }

    public function canBeCancelledBy(?User $user): bool
    {
        if (! $user || $this->requester_id !== $user->id) {
            return false;
        }

        return $this->status === 'open';
    }

    public function startsAt(): ?Carbon
    {
        if (! $this->session_date || ! $this->session_time) {
            return null;
        }

        try {
            return Carbon::parse($this->session_date->toDateString().' '.$this->session_time);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isAcceptCutoffPassed(): bool
    {
        if ($this->session_date && $this->session_date->lt(now()->startOfDay())) {
            return true;
        }

        $deadline = $this->acceptDeadline();

        return $deadline !== null && $deadline->lte(now());
    }

    /**
     * Close open requests nobody accepted in time. Runs when the lists load, so it
     * doesn't depend on a server cron job.
     */
    public static function expireStale(): void
    {
        static::query()
            ->where('status', 'open')
            ->where(function ($q) {
                $q->whereDate('session_date', '<=', now()->toDateString())
                    ->orWhere('know_by_at', '<=', now());
            })
            ->get()
            ->filter(fn (self $session) => $session->isAcceptCutoffPassed())
            ->each(fn (self $session) => $session->update(['status' => 'expired']));
    }

    /** Requests worth showing a coach: today or later. */
    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('session_date')
                ->orWhereDate('session_date', '>=', now()->toDateString());
        });
    }

    /** Most players this session can take, or null when the coach decides case by case. */
    public function capacity(): ?int
    {
        if ($this->max_players !== null) {
            return (int) $this->max_players;
        }

        return CoachGroupSession::isGroupType($this->session_type) ? null : 1;
    }

    public function placesTaken(): int
    {
        $this->loadMissing('players');

        return $this->players->filter(fn (SessionRequestPlayer $p) => $p->holdsPlace())->count();
    }

    public function confirmedCount(): int
    {
        $this->loadMissing('players');

        return $this->players->filter(fn (SessionRequestPlayer $p) => $p->isConfirmed())->count();
    }

    /** Families waiting for the coach to confirm them, not counting the requester. */
    public function pendingJoinCount(): int
    {
        $this->loadMissing('players');

        return $this->players->filter(fn (SessionRequestPlayer $p) => $p->isPending() && ! $p->isRequester())->count();
    }

    public function spotsLeft(): ?int
    {
        $capacity = $this->capacity();

        return $capacity === null ? null : max(0, $capacity - $this->placesTaken());
    }

    public function isFull(): bool
    {
        return $this->spotsLeft() === 0;
    }

    /** What a parent will owe the coach: the set price, or their budget until a coach sets one. */
    public function priceLabel(): string
    {
        if ($this->price_per_player !== null) {
            return '$'.rtrim(rtrim(number_format((float) $this->price_per_player, 2), '0'), '.').' per player';
        }

        return (string) ($this->price_range ?: '');
    }

    public function isAccepted(): bool
    {
        return in_array($this->status, ['hosted', 'awaiting_deposit', 'confirmed'], true) && $this->host_coach_id !== null;
    }

    public function playerFor(?User $user): ?SessionRequestPlayer
    {
        if (! $user) {
            return null;
        }

        $this->loadMissing('players');

        return $this->players->first(fn (SessionRequestPlayer $p) => (int) $p->user_id === (int) $user->id);
    }

    /**
     * The accepting coach's details, only for a family still on this session.
     *
     * @return array{name: string, email: ?string, phone: ?string, payment_note: ?string}|null
     */
    public function coachContactFor(?User $user): ?array
    {
        $player = $this->playerFor($user);
        if (! $player || ! $player->isActive() || ! $this->isAccepted()) {
            return null;
        }

        $this->loadMissing('hostCoach.user');
        $coach = $this->hostCoach;

        return [
            'name' => $coach?->display_name ?? 'Your coach',
            'email' => $coach?->user?->email,
            'phone' => $coach?->user?->phone,
            'payment_note' => $this->payment_note,
        ];
    }

    /**
     * Still waiting for a coach but its "accept by" deadline passed — nobody can host it,
     * so it shouldn't be advertised or joined.
     */
    public function isUnhostedAndExpired(): bool
    {
        return $this->status === 'open' && $this->isAcceptCutoffPassed();
    }

    public function isJoinClosed(): bool
    {
        $start = $this->startsAt();
        if (! $start) {
            return false;
        }

        return now()->gte($start->copy()->subMinutes(self::CUTOFF_MINUTES));
    }

    public function canBeViewedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $this->requester_id === (int) $user->id) {
            return true;
        }

        $this->loadMissing('players');
        if ($this->players->contains(fn ($player) => (int) $player->user_id === (int) $user->id)) {
            return true;
        }

        if ($user->isCoach() && $user->coach) {
            return $this->isVisibleToCoach($user->coach);
        }

        return false;
    }

    /**
     * Shape for the player dashboard request list.
     *
     * @return array<string, mixed>
     */
    public function toPlayerDashboardArray(?User $viewer = null): array
    {
        $this->loadMissing(['hostCoach.user', 'requestedCoach.user', 'location', 'players']);

        $role = 'requester';
        if ($viewer) {
            if ($this->requester_id === $viewer->id) {
                $role = 'requester';
            } elseif ($this->players->contains(fn ($p) => $p->user_id === $viewer->id)) {
                $role = 'joined';
            }
        }

        $this->loadMissing('invitedCoaches');
        $invitedNames = $this->invitedCoaches->pluck('display_name')->filter()->values();
        $coachName = $this->hostCoach?->display_name
            ?? ($invitedNames->count() === 1
                ? $invitedNames->first()
                : ($invitedNames->isNotEmpty()
                    ? $invitedNames->take(2)->implode(', ').($invitedNames->count() > 2 ? '…' : '')
                    : $this->requestedCoach?->display_name));

        $waiting = 'Waiting for a coach';
        if ($this->hostCoach) {
            $waiting = 'Hosted by '.$this->hostCoach->display_name;
        } elseif ($invitedNames->count() === 1) {
            $waiting = 'Waiting for '.$invitedNames->first();
        } elseif ($invitedNames->count() > 1) {
            $waiting = 'Waiting for '.$invitedNames->count().' selected coaches';
        } elseif ($this->requestedCoach) {
            $waiting = 'Waiting for '.$this->requestedCoach->display_name;
        }

        $place = $this->playerFor($viewer);
        [$statusLabel, $statusTone] = $this->placeLabelFor($place);

        return [
            'reference' => $this->reference,
            'when' => $this->whenLabel(),
            'date_label' => $this->session_date?->format('M j, Y') ?? '',
            'session_type' => $this->session_type ?? 'Session',
            'location' => $this->location_name ?? $this->location?->name ?? 'Location TBD',
            'status' => $this->status,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'place_status' => $place?->status,
            'price_label' => $this->priceLabel(),
            'coach_contact' => $this->coachContactFor($viewer),
            'coach' => $coachName,
            'requested_coach' => $coachName,
            'invited_coach_ids' => $this->invitedCoachIds(),
            'role' => $role,
            'role_label' => match (true) {
                $role === 'requester' => 'You requested',
                (bool) $place?->isConfirmed() => 'You joined',
                default => 'You asked to join',
            },
            'waiting_label' => $waiting,
            'can_cancel' => $this->canBeCancelledBy($viewer),
            'players_count' => $this->placesTaken(),
            'posted' => $this->postedLabel(),
        ];
    }

    /**
     * Label for a family's own place, which can differ from the session's status.
     *
     * @return array{0: string, 1: string}
     */
    private function placeLabelFor(?SessionRequestPlayer $place): array
    {
        if (! $place || ! in_array($this->status, ['open', 'hosted', 'awaiting_deposit', 'confirmed'], true)) {
            return [$this->statusLabel(), $this->statusTone()];
        }

        return match (true) {
            $place->status === SessionRequestPlayer::DECLINED => ['Not confirmed', 'red'],
            $place->status === SessionRequestPlayer::CANCELLED => ['Cancelled', 'zinc'],
            $place->isConfirmed() => ['Confirmed', 'green'],
            ! $place->isRequester() => ['Pending coach approval', 'amber'],
            $this->isAccepted() => ['Accepted · Arrange payment', 'amber'],
            default => [$this->statusLabel(), $this->statusTone()],
        };
    }

    /**
     * Shape expected by coach portal blade + JS.
     *
     * @return array<string, mixed>
     */
    public function toPortalArray(): array
    {
        $this->loadMissing(['players', 'requester', 'hostCoach.user', 'requestedCoach.user']);

        $requesterPlayer = $this->players->firstWhere('role', 'requester');
        $name = $requesterPlayer?->name
            ?? $this->requester?->name
            ?? 'Player';
        $initials = $requesterPlayer?->initials
            ?? Str::of($name)->explode(' ')->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

        $players = $this->players
            ->filter(fn (SessionRequestPlayer $player) => $player->isActive())
            ->map(fn (SessionRequestPlayer $player) => [
                'id' => 'p-'.$player->id,
                'player_id' => $player->id,
                'name' => $player->name,
                'initials' => $player->initials,
                'role' => $player->role,
                'status' => $player->status,
            ])->values()->all();

        $looking = $this->spotsLeft();

        return [
            'id' => $this->reference,
            'initials' => $initials,
            'name' => $name,
            'location' => $this->location_name ?? $this->location?->name ?? '',
            'city' => $this->location_city ?? $this->location?->area ?? '',
            'when' => $this->whenLabel(),
            'session_type' => $this->session_type,
            'age_range' => $this->age_range,
            'price_range' => $this->price_range,
            'sport' => $this->sport ?: '—',
            'notes' => $this->notes,
            'min_players' => $this->min_players ?? '',
            'max_players' => $this->max_players ?? '',
            'player_level' => $this->player_level,
            'know_by' => $this->knowByLabel(),
            'price_per_player' => $this->price_per_player !== null ? (float) $this->price_per_player : null,
            'price_label' => $this->priceLabel(),
            'status' => $this->status,
            'accept_seconds' => $this->acceptSeconds(),
            'acceptExpiresAt' => ($deadline = $this->acceptDeadline()) ? ((int) $deadline->getTimestamp() * 1000) : null,
            'posted' => $this->postedLabel(),
            'accepted_by' => $this->hostCoach?->display_name,
            'host_coach_id' => $this->host_coach_id,
            'requested_coach_id' => $this->requested_coach_id,
            'requested_coach' => $this->requestedCoach?->display_name,
            'players_joined' => $this->placesTaken(),
            'confirmed_count' => $this->confirmedCount(),
            'pending_count' => $this->pendingJoinCount(),
            'looking_for' => $looking ?? '',
            'coach_note' => $this->coach_note,
            'players' => $players,
            'createdAt' => $this->created_at ? ((int) $this->created_at->getTimestamp() * 1000) : null,
        ];
    }

    /**
     * Portal data for a coach. Only the accepting coach gets the families' contact details,
     * which is why they aren't in toPortalArray(): that goes out on a public channel.
     *
     * @return array<string, mixed>
     */
    public function toCoachPortalArray(?Coach $coach): array
    {
        $data = $this->toPortalArray();
        if (! $coach || (int) $this->host_coach_id !== (int) $coach->id) {
            return $data;
        }

        $this->loadMissing('players.user');
        $people = $this->players->keyBy('id');
        $data['payment_note'] = $this->payment_note;
        $data['players'] = array_map(function (array $player) use ($people) {
            $user = $people->get($player['player_id'])?->user;

            return $player + ['email' => $user?->email, 'phone' => $user?->phone];
        }, $data['players']);

        return $data;
    }

    /**
     * Portal data for a family on this session.
     *
     * @return array<string, mixed>
     */
    public function toFamilyPortalArray(?User $user): array
    {
        return $this->toPortalArray() + [
            'coach_contact' => $this->coachContactFor($user),
            'my_place' => $this->playerFor($user)?->status,
        ];
    }
}
