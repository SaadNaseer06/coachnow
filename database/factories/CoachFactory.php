<?php

namespace Database\Factories;

use App\Models\Coach;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coach>
 */
class CoachFactory extends Factory
{
    protected $model = Coach::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => User::ROLE_COACH]),
            'location_id' => Location::factory(),
            'display_name' => 'Coach '.fake()->firstName(),
            'specialty' => fake()->randomElement([
                'Private Soccer Training',
                'Small Group Soccer',
                'Team Training',
                'Performance & Speed',
            ]),
            'sport' => fake()->randomElement(User::SPORTS),
            'experience' => fake()->randomElement(['1-3 years', '4-5 years', '6-7 years', '8+ years']),
            'ages' => 'Ages 8–14',
            'status' => 'active',
            'rate' => fake()->randomElement([35, 40, 45, 50, 55]),
            'rating' => fake()->randomFloat(1, 4.5, 5.0),
            'reviews_count' => fake()->numberBetween(20, 150),
            'photo_path' => 'assets/Rectangle 8.png',
            'bio' => fake()->sentence(12),
        ];
    }
}
