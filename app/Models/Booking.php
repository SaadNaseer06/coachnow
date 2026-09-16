<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'session_request_id',
        'coach_id',
        'athlete_id',
        'location_id',
        'athlete_name',
        'session_type',
        'session_date',
        'session_time',
        'duration_minutes',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'amount' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function sessionRequest(): BelongsTo
    {
        return $this->belongsTo(SessionRequest::class);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'CN-'.random_int(1000, 9999);
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function scopeForCoach(Builder $query, int $coachId): Builder
    {
        return $query->where('coach_id', $coachId);
    }

    public function scopeForAthlete(Builder $query, int $athleteId): Builder
    {
        return $query->where('athlete_id', $athleteId);
    }

    public function scopeInWeek(Builder $query, Carbon $weekStart): Builder
    {
        $start = $weekStart->copy()->startOfDay();
        $end = $weekStart->copy()->addDays(6)->endOfDay();

        return $query->whereBetween('session_date', [$start->toDateString(), $end->toDateString()]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'cancelled')
            ->where(fn (Builder $q) => $this->constrainNotEnded($q))
            ->orderBy('session_date')
            ->orderBy('session_time');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'cancelled')
            ->where(fn (Builder $q) => $this->constrainEnded($q))
            ->orderByDesc('session_date')
            ->orderByDesc('session_time');
    }

    /**
     * Still on the calendar: future date, or today and the 60-minute slot has not ended.
     */
    public function isUpcoming(): bool
    {
        if ($this->status === 'cancelled' || ! $this->session_date) {
            return false;
        }

        $start = $this->startsAt();
        if (! $start) {
            return $this->session_date->toDateString() >= now()->toDateString();
        }

        return $start->copy()->addMinutes($this->durationMinutes())->gt(now());
    }

    public function durationMinutes(): int
    {
        $minutes = (int) ($this->duration_minutes ?: 0);

        return $minutes > 0 ? $minutes : SessionRequest::defaultDurationMinutes();
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

    private function constrainNotEnded(Builder $query): Builder
    {
        $today = now()->toDateString();
        $latestStart = now()->subMinutes(SessionRequest::defaultDurationMinutes())->format('H:i:s');

        return $query->where(function (Builder $q) use ($today, $latestStart) {
            $q->whereDate('session_date', '>', $today)
                ->orWhere(function (Builder $inner) use ($today, $latestStart) {
                    $inner->whereDate('session_date', $today)
                        ->where(function (Builder $time) use ($latestStart) {
                            $time->whereNull('session_time')
                                ->orWhereTime('session_time', '>', $latestStart);
                        });
                });
        });
    }

    private function constrainEnded(Builder $query): Builder
    {
        $today = now()->toDateString();
        $latestStart = now()->subMinutes(SessionRequest::defaultDurationMinutes())->format('H:i:s');

        return $query->where(function (Builder $q) use ($today, $latestStart) {
            $q->whereDate('session_date', '<', $today)
                ->orWhere(function (Builder $inner) use ($today, $latestStart) {
                    $inner->whereDate('session_date', $today)
                        ->whereNotNull('session_time')
                        ->whereTime('session_time', '<=', $latestStart);
                });
        });
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'pending']);
    }

    public function whenLabel(): string
    {
        $date = $this->session_date?->format('D, M j') ?? '';
        $time = '';

        if ($this->session_time) {
            try {
                $time = Carbon::parse($this->session_time)->format('g:i A');
            } catch (\Throwable) {
                $time = substr((string) $this->session_time, 0, 8);
            }
        }

        return collect([$date, $time])->filter()->implode(' · ');
    }

    public function displayName(): string
    {
        return $this->athlete_name
            ?: $this->athlete?->name
            ?: 'Player';
    }

    public function athleteSlug(): string
    {
        if ($this->athlete_id) {
            return (string) $this->athlete_id;
        }

        return Str::slug($this->displayName()) ?: 'player';
    }

    public function tone(): string
    {
        $type = strtolower((string) $this->session_type);

        return match (true) {
            str_contains($type, 'small') => 'yellow',
            str_contains($type, 'group') || str_contains($type, 'team') => 'purple',
            str_contains($type, 'assess') => 'orange',
            default => 'green',
        };
    }
}
