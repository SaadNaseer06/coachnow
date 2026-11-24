<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One CoachNow fee checkout for a Book Now booking.
 */
class BookingCheckout extends Model
{
    /** Waiting for the parent to pay. */
    public const PENDING = 'pending';

    /** Paid and booked. */
    public const BOOKED = 'booked';

    /** Paid, but the time was gone: the fee still has to be returned. */
    public const REFUND_DUE = 'refund_due';

    /** Paid, not booked, fee returned. */
    public const REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'coach_id',
        'kind',
        'payload',
        'sessions',
        'fee_per_session',
        'fee_cents',
        'stripe_payment_intent_id',
        'status',
        'booking_ids',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'booking_ids' => 'array',
            'sessions' => 'integer',
            'fee_cents' => 'integer',
            'fee_per_session' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * @return list<Booking>
     */
    public function bookings(): array
    {
        $ids = $this->booking_ids ?? [];

        return $ids === [] ? [] : Booking::query()
            ->with(['coach.user', 'location', 'groupSession'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->all();
    }
}
