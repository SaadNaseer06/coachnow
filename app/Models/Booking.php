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
            ->where('session_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('session_date')
            ->orderBy('session_time');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->where(function ($q) {
                $q->where('session_date', '<', now()->toDateString())
                    ->orWhere(function ($inner) {
                        $inner->whereDate('session_date', now()->toDateString())
                            ->whereTime('session_time', '<', now()->format('H:i:s'));
                    });
            })
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('session_date')
            ->orderByDesc('session_time');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'pending']);
    }

    public function whenLabel(): string
    {
        $date = $this->session_date?->format('D, M j') ?? '';
        $time = $this->session_time
            ? Carbon::parse($this->session_time)->format('g:i A')
            : '';

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
