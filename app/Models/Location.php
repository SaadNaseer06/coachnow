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
        'coach_id',
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

    /** Admin parks, plus venues this coach entered themselves. */
    public function scopeUsableBy($query, Coach $coach)
    {
        return $query->where(fn ($q) => $q->where('status', 'live')->orWhere('coach_id', $coach->id));
    }

    /** "Sommers Bend · Temecula" for parks; for a coach's own venue the area is the street address. */
    public function label(): string
    {
        return $this->name.($this->area ? ' · '.$this->area : '');
    }

    public function directionsUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim($this->name.', '.$this->area, ', '));
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
