<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = fake()->unique()->city().' Park';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'area' => fake()->city().', CA',
            'distance_miles' => fake()->randomFloat(1, 1, 12),
            'status' => 'live',
            'image_path' => 'assets/Background.png',
        ];
    }
}
