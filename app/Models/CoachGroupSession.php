<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CoachGroupSession extends Model
{
    protected $fillable = [
        'coach_id',
        'location_id',
        'session_type',
        'sport',
        'session_date',
        'session_time',
        'duration_minutes',
        'max_players',
        'notes',
        'status',
        'is_public',
        'age_range',
        'price_per_player',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'duration_minutes' => 'integer',
            'max_players' => 'integer',
            'is_public' => 'boolean',
            'price_per_player' => 'decimal:2',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'group_session_id');
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(GroupJoinRequest::class, 'group_session_id');
    }

    public static function isGroupType(?string $sessionType): bool
    {
        $type = strtolower((string) $sessionType);

        if ($type === '' || str_contains($type, 'private') || str_contains($type, '1-on-1') || str_contains($type, '1 on 1')) {
            return false;
        }

        return str_contains($type, 'group')
            || str_contains($type, 'camp')
            || str_contains($type, 'clinic')
            || str_contains($type, 'team');
    }

    public const MIN_AGE = 3;

    public const MAX_AGE = 25;

    /**
     * Coach-entered bounds → stored label, e.g. 11 & 13 → "Ages 11-13", 12 & 12 → "Age 12".
     */
    public static function ageRangeFromBounds(mixed $min, mixed $max): ?string
    {
        if (blank($min) && blank($max)) {
            return null;
        }

        $min = (int) (filled($min) ? $min : $max);
        $max = (int) (filled($max) ? $max : $min);
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return $min === $max ? "Age {$min}" : "Ages {$min}-{$max}";
    }

    /**
     * Stored label → [min, max] for pre-filling the coach form. Handles legacy
     * values like "All ages" (null) and "Ages 10-16".
     *
     * @return array{0:int,1:int}|null
     */
    public static function ageBounds(?string $ageRange): ?array
    {
        if (! $ageRange) {
            return null;
        }

        if (preg_match('/(\d{1,2})\s*(?:-|–|to)\s*(\d{1,2})/i', $ageRange, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        if (preg_match('/(\d{1,2})/', $ageRange, $m)) {
            return [(int) $m[1], (int) $m[1]];
        }

        return null;
    }

    /**
     * The group's own sport, falling back to the coach's primary sport for older rows.
     */
    public function sportLabel(): ?string
    {
        return $this->sport ?: ($this->coach?->sportsList()[0] ?? ($this->coach?->sport ?: null));
    }

    public function activeBookings(): HasMany
    {
        return $this->bookings()->where('status', '!=', 'cancelled');
    }

    public function bookedCount(): int
    {
        return (int) $this->activeBookings()->count();
    }

    public function spotsRemaining(): int
    {
        return max(0, (int) $this->max_players - $this->bookedCount());
    }

    public function isJoinable(): bool
    {
        return $this->is_public
            && $this->status === 'open'
            && $this->spotsRemaining() > 0;
    }

    public function openToPublic(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $this->ensureShareToken();
        $this->forceFill(['is_public' => true])->save();
        $this->syncStatusFromRoster();
    }

    public function closeToPublic(): void
    {
        $this->forceFill(['is_public' => false])->save();
    }

    public function ensureShareToken(): string
    {
        if (filled($this->share_token)) {
            return (string) $this->share_token;
        }

        do {
            $token = Str::lower(Str::random(10));
        } while (static::query()->where('share_token', $token)->exists());

        $this->forceFill(['share_token' => $token])->save();

        return $token;
    }

    public function shareUrl(): ?string
    {
        if (! filled($this->share_token)) {
            return null;
        }

        return route('group-session.share', ['token' => $this->share_token]);
    }

    public function pricePerPlayerLabel(): ?string
    {
        if ($this->price_per_player === null) {
            return null;
        }

        return '$'.number_format((float) $this->price_per_player, 0).' per player';
    }

    /**
     * Parent-facing summary for Book Now / discovery cards.
     *
     * @return array<string, mixed>
     */
    public function toParentSlotArray(): array
    {
        $booked = $this->bookedCount();
        $max = (int) $this->max_players;
        $spots = max(0, $max - $booked);
        $isFull = $spots <= 0 || $this->status === 'full';

        return [
            'group_session_id' => $this->id,
            'session_type' => $this->session_type,
            'sport' => $this->sportLabel(),
            'age_range' => $this->age_range,
            'price_per_player' => $this->price_per_player !== null ? (float) $this->price_per_player : null,
            'price_label' => $this->pricePerPlayerLabel(),
            'booked_count' => $booked,
            'max_players' => $max,
            'spots_remaining' => $spots,
            'full' => $isFull,
            'is_public' => (bool) $this->is_public,
            'joinable' => $this->isJoinable(),
            'capacity_label' => $this->capacityLabel(),
            'label' => $isFull
                ? $this->session_type.' — '.$booked.' of '.$max.' booked / Full'
                : $this->session_type.' — '.$booked.' of '.$max.' booked / '.$spots.' spot'.($spots === 1 ? '' : 's').' remaining',
        ];
    }

    public function syncStatusFromRoster(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $this->forceFill([
            'status' => $this->spotsRemaining() > 0 ? 'open' : 'full',
        ])->save();
    }

    public function timeLabel(): string
    {
        return Carbon::parse($this->session_time)->format('g:i A');
    }

    public function capacityLabel(): string
    {
        $booked = $this->bookedCount();
        $max = (int) $this->max_players;

        return "{$booked} of {$max} booked";
    }

    public function durationMinutes(): int
    {
        return max(30, (int) ($this->duration_minutes ?: 60));
    }
}
