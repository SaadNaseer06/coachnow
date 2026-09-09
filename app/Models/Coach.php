<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coach extends Model
{
    protected $fillable = [
        'user_id',
        'location_id',
        'display_name',
        'specialty',
        'experience',
        'status',
        'rate',
        'rating',
        'reviews_count',
        'photo_path',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'rating' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
