<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_COACH = 'coach';

    public const ROLE_ATHLETE = 'athlete';

    /**
     * @var list<string>
     */
    public const SPORTS = [
        'Soccer',
        'Futsal',
        'Basketball',
        'Baseball',
        'Softball',
        'Tennis',
        'Performance & Speed',
    ];

    public static function sportFilterValue(?string $sport): string
    {
        $sport = strtolower(trim((string) $sport));

        if ($sport === '') {
            return '';
        }

        if (str_contains($sport, 'performance') || str_contains($sport, 'speed')) {
            return 'fitness';
        }

        return str_replace([' & ', ' '], ['-', '-'], $sport);
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
        'subscription_ends_at',
        'subscription_cancel_at_period_end',
        'sport',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_ends_at' => 'datetime',
            'subscription_cancel_at_period_end' => 'boolean',
        ];
    }

    /**
     * Family membership is active: paid (or trialing) and the current period hasn't ended.
     * A membership cancelled "at period end" stays active until that date.
     */
    public function hasActiveSubscription(): bool
    {
        if (! in_array($this->subscription_status, ['active', 'trialing'], true)) {
            return false;
        }

        return $this->subscription_ends_at === null || $this->subscription_ends_at->isFuture();
    }

    /**
     * Member-only features (session requests, coach reports). Can be switched off
     * with COACHNOW_GATE_MEMBER_FEATURES=false while testing.
     */
    public function hasMemberAccess(): bool
    {
        return ! config('coachnow.subscription.gate_features', true) || $this->hasActiveSubscription();
    }

    /**
     * $2 convenience fee per checkout, waived for members.
     */
    public static function convenienceFeeFor(?self $user): float
    {
        if ($user?->hasActiveSubscription()) {
            return 0.0;
        }

        return (float) config('coachnow.payments.convenience_fee', 2);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCoach(): bool
    {
        return $this->role === self::ROLE_COACH;
    }

    public function isAthlete(): bool
    {
        return $this->role === self::ROLE_ATHLETE;
    }

    public function dashboardPath(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => route('admin.dashboard'),
            self::ROLE_COACH => route('coach.dashboard'),
            default => route('player-dashboard'),
        };
    }

    public function coach(): HasOne
    {
        return $this->hasOne(Coach::class);
    }

    public function athleteBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'athlete_id');
    }

    public function sharedVideos(): HasMany
    {
        return $this->hasMany(SharedVideo::class, 'athlete_id');
    }
}
