<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A private note a coach keeps about one player. Only that coach can see it. */
class CoachPlayerNote extends Model
{
    protected $fillable = [
        'coach_id',
        'athlete_id',
        'athlete_name',
        'body',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
