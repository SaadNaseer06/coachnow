<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SessionReport extends Model
{
    protected $fillable = [
        'coach_id',
        'athlete_id',
        'athlete_name',
        'keywords',
        'focus',
        'went_well',
        'needs_work',
        'home_plan',
        'summary',
        'recommended_videos',
        'shared_with_player',
        'shared_at',
        'ai_source',
    ];

    protected function casts(): array
    {
        return [
            'recommended_videos' => 'array',
            'shared_with_player' => 'boolean',
            'shared_at' => 'datetime',
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

    public function scopeForAthlete(Builder $query, User $athlete): Builder
    {
        return $query->where(function (Builder $q) use ($athlete) {
            $q->where('athlete_id', $athlete->id)
                ->orWhere(function (Builder $inner) use ($athlete) {
                    $inner->whereNull('athlete_id')
                        ->whereRaw('LOWER(athlete_name) = ?', [mb_strtolower(trim($athlete->name))]);
                });
        });
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('shared_with_player', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDisplayArray(): array
    {
        $this->loadMissing('coach.user');

        return [
            'id' => $this->id,
            'keywords' => $this->keywords,
            'focus' => $this->focus,
            'went_well' => $this->went_well,
            'needs_work' => $this->needs_work,
            'home_plan' => $this->home_plan,
            'summary' => $this->summary ?: Str::limit(strip_tags((string) $this->focus), 160),
            'recommended_videos' => $this->recommended_videos ?: [],
            'shared' => (bool) $this->shared_with_player,
            'coach' => $this->coach?->display_name,
            'created_at' => $this->created_at?->format('M j, Y') ?? '',
            'shared_at' => $this->shared_at?->diffForHumans() ?? '',
            'created_human' => $this->created_at?->diffForHumans() ?? '',
        ];
    }
}
