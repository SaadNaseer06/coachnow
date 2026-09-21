<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NominatimGeocoder
{
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    private const USER_AGENT = 'CoachNow/1.0 (coach discovery; contact@coachnow.app)';

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    public function geocode(string $query): ?array
    {
        $normalized = $this->normalizeQuery($query);
        if ($normalized === '') {
            return null;
        }

        $cacheKey = 'nominatim:geocode:'.sha1($normalized);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($normalized) {
            return $this->fetchFromNominatim($normalized);
        });
    }

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    public function geocodePark(string $name, string $area): ?array
    {
        $parts = array_filter([trim($name), trim($area)]);
        if ($parts === []) {
            return null;
        }

        return $this->geocode(implode(', ', $parts).', USA');
    }

    private function normalizeQuery(string $query): string
    {
        return Str::of($query)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    private function fetchFromNominatim(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'us',
                    'addressdetails' => 0,
                ]);

            if (! $response->successful()) {
                Log::warning('Nominatim geocode failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $first = $response->json('0');
            if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
                return null;
            }

            $lat = (float) $first['lat'];
            $lng = (float) $first['lon'];
            if (! is_finite($lat) || ! is_finite($lng)) {
                return null;
            }

            $label = trim((string) ($first['display_name'] ?? $query));
            if ($label === '') {
                $label = $query;
            }

            return [
                'lat' => $lat,
                'lng' => $lng,
                'label' => $label,
            ];
        } catch (\Throwable $e) {
            Log::warning('Nominatim geocode exception', [
                'query' => $query,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
