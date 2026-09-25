<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CoachAvailabilitySlot extends Model
{
    protected $fillable = [
        'coach_id',
        'location_id',
        'day_of_week',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
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

    public function startTimeLabel(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A');
    }

    public function endTimeLabel(): string
    {
        return Carbon::parse($this->end_time)->format('g:i A');
    }

    public function dayName(): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$this->day_of_week] ?? 'Day';
    }
}
