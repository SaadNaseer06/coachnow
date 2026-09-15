<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'area',
        'distance_miles',
        'status',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'distance_miles' => 'decimal:1',
        ];
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(Coach::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
