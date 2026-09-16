<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function players(): HasMany
    {
        return $this->hasMany(SessionRequestPlayer::class);
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

        // Targeted request: only the chosen coach.
        if ($this->requested_coach_id !== null) {
            return (int) $this->requested_coach_id === (int) $coach->id;
        }

        // Open marketplace request: any active coach.
        return $coach->status === 'active';
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
                                ->orWhereNull('requested_coach_id');
                        });
                });
        });
    }

    /**
     * Conflict if the athlete or target coach already has an active slot at this date/time.
     */
    public static function hasSchedulingConflict(
        int $athleteId,
        string $sessionDate,
        ?string $sessionTime,
        ?int $coachId = null,
        ?int $ignoreRequestId = null,
    ): bool {
        if ($sessionTime === null || $sessionTime === '') {
            return false;
        }

        $activeStatuses = ['open', 'hosted', 'awaiting_deposit', 'confirmed'];

        $athleteBusy = static::query()
            ->where('requester_id', $athleteId)
            ->whereDate('session_date', $sessionDate)
            ->where('session_time', $sessionTime)
            ->whereIn('status', $activeStatuses)
            ->when($ignoreRequestId, fn ($q) => $q->where('id', '!=', $ignoreRequestId))
            ->exists();

        if ($athleteBusy) {
            return true;
        }

        $athleteBookingBusy = Booking::query()
            ->where('athlete_id', $athleteId)
            ->whereDate('session_date', $sessionDate)
            ->where('session_time', $sessionTime)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($athleteBookingBusy) {
            return true;
        }

        if (! $coachId) {
            return false;
        }

        $coachRequestBusy = static::query()
            ->where(function ($q) use ($coachId) {
                $q->where('requested_coach_id', $coachId)
                    ->orWhere('host_coach_id', $coachId);
            })
            ->whereDate('session_date', $sessionDate)
            ->where('session_time', $sessionTime)
            ->whereIn('status', $activeStatuses)
            ->when($ignoreRequestId, fn ($q) => $q->where('id', '!=', $ignoreRequestId))
            ->exists();

        if ($coachRequestBusy) {
            return true;
        }

        return Booking::query()
            ->where('coach_id', $coachId)
            ->whereDate('session_date', $sessionDate)
            ->where('session_time', $sessionTime)
            ->where('status', '!=', 'cancelled')
            ->exists();
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

    public function knowByLabel(): string
    {
        return $this->know_by_at
            ? $this->know_by_at->format('D, M j · g:i A')
            : '';
    }

    public function acceptSeconds(): int
    {
        if (! $this->know_by_at) {
            return 0;
        }

        return max(0, $this->know_by_at->getTimestamp() - now()->getTimestamp());
    }

    public function postedLabel(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Awaiting coach',
            'hosted', 'awaiting_deposit', 'confirmed' => 'Accepted',
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

        $coachName = $this->hostCoach?->display_name
            ?? $this->requestedCoach?->display_name;

        return [
            'reference' => $this->reference,
            'when' => $this->whenLabel(),
            'date_label' => $this->session_date?->format('M j, Y') ?? '',
            'session_type' => $this->session_type ?? 'Session',
            'location' => $this->location_name ?? $this->location?->name ?? 'Location TBD',
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'status_tone' => $this->statusTone(),
            'coach' => $coachName,
            'requested_coach' => $this->requestedCoach?->display_name,
            'role' => $role,
            'role_label' => $role === 'requester' ? 'You requested' : 'You joined',
            'waiting_label' => $this->hostCoach
                ? 'Hosted by '.$this->hostCoach->display_name
                : ($this->requestedCoach
                    ? 'Waiting for '.$this->requestedCoach->display_name
                    : 'Waiting for a coach'),
            'can_cancel' => $this->canBeCancelledBy($viewer),
            'players_count' => $this->players->count(),
            'posted' => $this->postedLabel(),
        ];
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

        $players = $this->players->map(fn (SessionRequestPlayer $player) => [
            'id' => 'p-'.$player->id,
            'name' => $player->name,
            'initials' => $player->initials,
            'role' => $player->role,
            'paid' => (bool) $player->paid,
            'paid_with' => $player->paid_with ?? '',
            'card_on_file' => $player->card_on_file ?? '',
        ])->values()->all();

        $looking = $this->looking_for;
        if ($looking === null && $this->max_players !== null) {
            $looking = max(0, $this->max_players - count($players));
        }

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
            'sport' => $this->sport ?? 'Soccer',
            'notes' => $this->notes,
            'min_players' => $this->min_players ?? '',
            'max_players' => $this->max_players ?? '',
            'player_level' => $this->player_level,
            'know_by' => $this->knowByLabel(),
            'deposit' => (float) $this->deposit_amount,
            'card_on_file' => $this->card_on_file,
            'status' => $this->status,
            'accept_seconds' => $this->acceptSeconds(),
            'acceptExpiresAt' => $this->know_by_at ? ((int) $this->know_by_at->getTimestamp() * 1000) : null,
            'posted' => $this->postedLabel(),
            'accepted_by' => $this->hostCoach?->display_name,
            'host_coach_id' => $this->host_coach_id,
            'requested_coach_id' => $this->requested_coach_id,
            'requested_coach' => $this->requestedCoach?->display_name,
            'players_joined' => count($players),
            'looking_for' => $looking ?? '',
            'coach_note' => $this->coach_note,
            'players' => $players,
            'deposit_paid' => collect($players)->contains(fn ($p) => ($p['role'] ?? '') === 'requester' && ! empty($p['paid'])),
            'createdAt' => $this->created_at ? ((int) $this->created_at->getTimestamp() * 1000) : null,
        ];
    }
}
