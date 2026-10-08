<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupJoinRequest extends Model
{
    protected $fillable = [
        'group_session_id',
        'athlete_id',
        'athlete_name',
        'notes',
        'status',
    ];

    public function groupSession(): BelongsTo
    {
        return $this->belongsTo(CoachGroupSession::class, 'group_session_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function displayName(): string
    {
        return $this->athlete_name
            ?: $this->athlete?->name
            ?: 'Player';
    }
}
