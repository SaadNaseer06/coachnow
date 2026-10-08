<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CoachGroupSession extends Model
{
    protected $fillable = [
        'coach_id',
        'location_id',
        'session_type',
        'session_date',
        'session_time',
        'duration_minutes',
        'max_players',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'duration_minutes' => 'integer',
            'max_players' => 'integer',
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
        return $this->status === 'open' && $this->spotsRemaining() > 0;
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
