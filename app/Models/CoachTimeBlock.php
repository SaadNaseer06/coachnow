<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CoachTimeBlock extends Model
{
    protected $fillable = [
        'coach_id',
        'title',
        'block_date',
        'start_time',
        'end_time',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'block_date' => 'date',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function durationMinutes(): int
    {
        $start = Carbon::parse($this->block_date->toDateString().' '.$this->normalizeTime($this->start_time));
        $end = Carbon::parse($this->block_date->toDateString().' '.$this->normalizeTime($this->end_time));

        return max(15, (int) $start->diffInMinutes($end));
    }

    public function startTimeLabel(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A');
    }

    public function endTimeLabel(): string
    {
        return Carbon::parse($this->end_time)->format('g:i A');
    }

    private function normalizeTime(mixed $time): string
    {
        $raw = (string) $time;
        if (preg_match('/^\d{2}:\d{2}$/', $raw)) {
            return $raw.':00';
        }

        return $raw;
    }
}
