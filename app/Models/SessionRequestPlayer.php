<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRequestPlayer extends Model
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const DECLINED = 'declined';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'session_request_id',
        'user_id',
        'name',
        'initials',
        'role',
        'status',
        'confirmed_at',
        'paid',
        'paid_with',
        'card_on_file',
        'stripe_payment_method_id',
        'stripe_payment_intent_id',
    ];

    protected function casts(): array
    {
        return [
            'paid' => 'boolean',
            'confirmed_at' => 'datetime',
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

    public function isRequester(): bool
    {
        return $this->role === 'requester';
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::CONFIRMED;
    }

    /**
     * Confirmed players take a place. The family who made the request keeps theirs while the
     * coach waits for the deposit; other pending families don't hold one.
     */
    public function holdsPlace(): bool
    {
        return $this->isConfirmed() || ($this->isPending() && $this->isRequester());
    }

    /** Still on the session, so they can reach the coach. */
    public function isActive(): bool
    {
        return $this->isConfirmed() || $this->isPending();
    }
}
