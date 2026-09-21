<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'slug' => 'sommers-bend',
                'name' => 'Sommers Bend',
                'area' => 'Murrieta, CA',
                'distance_miles' => 1.2,
                'latitude' => 33.5824,
                'longitude' => -117.1486,
                'image_path' => 'assets/Background (1).png',
                'status' => 'live',
            ],
            [
                'slug' => 'birdsall',
                'name' => 'Birdsall',
                'area' => 'Temecula, CA',
                'distance_miles' => 2.4,
                'latitude' => 33.4936,
                'longitude' => -117.1484,
                'image_path' => 'assets/Background.png',
                'status' => 'live',
            ],
            [
                'slug' => 'los-alamos',
                'name' => 'Los Alamos',
                'area' => 'Murrieta, CA',
                'distance_miles' => 3.1,
                'latitude' => 33.5754,
                'longitude' => -117.1478,
                'image_path' => 'assets/hero-bg.png',
                'status' => 'live',
            ],
            [
                'slug' => 'alta-murrieta',
                'name' => 'Alta Murrieta',
                'area' => 'Murrieta, CA',
                'distance_miles' => 4.0,
                'latitude' => 33.5918,
                'longitude' => -117.1702,
                'image_path' => 'assets/Background (1).png',
                'status' => 'live',
            ],
            [
                'slug' => 'temecula-sports',
                'name' => 'Temecula Sports Park',
                'area' => 'Temecula, CA',
                'distance_miles' => 5.2,
                'latitude' => 33.4935,
                'longitude' => -117.1484,
                'image_path' => 'assets/Background.png',
                'status' => 'live',
            ],
            [
                'slug' => 'california-oaks',
                'name' => 'California Oaks',
                'area' => 'Murrieta, CA',
                'distance_miles' => 6.8,
                'latitude' => 33.5531,
                'longitude' => -117.2052,
                'image_path' => 'assets/hero-bg.png',
                'status' => 'live',
            ],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['slug' => $location['slug']],
                $location
            );
        }
    }
}
