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
        'latitude',
        'longitude',
        'status',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'distance_miles' => 'decimal:1',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && is_finite((float) $this->latitude)
            && is_finite((float) $this->longitude);
    }

    /**
     * Great-circle distance in miles between this park and an origin point.
     */
    public function distanceMilesFrom(float $lat, float $lng): ?float
    {
        if (! $this->hasCoordinates()) {
            return null;
        }

        return self::haversineMiles(
            $lat,
            $lng,
            (float) $this->latitude,
            (float) $this->longitude
        );
    }

    public static function haversineMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 3958.7613;
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return round($earthRadius * 2 * asin(min(1, sqrt($a))), 1);
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
