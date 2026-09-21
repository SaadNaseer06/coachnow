<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\NominatimGeocoder;
use Illuminate\Console\Command;

class GeocodeLocationsCommand extends Command
{
    protected $signature = 'locations:geocode
                            {--force : Re-geocode even when coordinates already exist}';

    protected $description = 'Backfill latitude/longitude for parks so Find a Coach Near me works';

    public function handle(NominatimGeocoder $geocoder): int
    {
        $query = Location::query()->orderBy('id');
        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        $locations = $query->get();
        if ($locations->isEmpty()) {
            $this->info('All locations already have coordinates.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($locations as $location) {
            // Nominatim fair-use: max ~1 request/second
            usleep(1_100_000);

            $coords = $geocoder->geocodePark((string) $location->name, (string) $location->area);
            if (! $coords) {
                $this->warn("Failed: {$location->name} ({$location->area})");
                $fail++;
                continue;
            }

            $location->forceFill([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
            ])->save();

            $this->line("OK: {$location->name} → {$coords['lat']}, {$coords['lng']}");
            $ok++;
        }

        $this->info("Done. Geocoded {$ok}, failed {$fail}.");

        return $fail > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
