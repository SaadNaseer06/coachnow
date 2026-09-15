<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRequestPlayer extends Model
{
    protected $fillable = [
        'session_request_id',
        'user_id',
        'name',
        'initials',
        'role',
        'paid',
        'paid_with',
        'card_on_file',
    ];

    protected function casts(): array
    {
        return [
            'paid' => 'boolean',
        ];
    }

    public function sessionRequest(): BelongsTo
    {
        return $this->belongsTo(SessionRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
