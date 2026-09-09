<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
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
}
