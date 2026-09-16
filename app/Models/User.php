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
        ];
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
        return in_array($this->role, [self::ROLE_ATHLETE, 'player'], true);
    }

    public function displaySport(): ?string
    {
        if (! array_key_exists('sport', $this->getAttributes())) {
            return null;
        }

        $sport = trim((string) $this->getAttributes()['sport']);

        return $sport !== '' ? $sport : null;
    }

    public function dashboardPath(): string
    {
        return match ($this->normalizedRole()) {
            self::ROLE_ADMIN => route('admin.dashboard'),
            self::ROLE_COACH => route('coach.dashboard'),
            default => route('player-dashboard'),
        };
    }

    public function normalizedRole(): string
    {
        return $this->role === 'player' ? self::ROLE_ATHLETE : (string) $this->role;
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
