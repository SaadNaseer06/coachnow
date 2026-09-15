<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'reference' => 'CN-'.fake()->unique()->numerify('####'),
            'coach_id' => Coach::factory(),
            'athlete_id' => User::factory()->state(['role' => User::ROLE_ATHLETE]),
            'location_id' => Location::factory(),
            'athlete_name' => fake()->name(),
            'session_type' => fake()->randomElement(['Private', 'Small Group', 'Team', 'Performance']),
            'session_date' => fake()->dateTimeBetween('now', '+14 days')->format('Y-m-d'),
            'session_time' => fake()->randomElement(['09:00:00', '16:00:00', '17:30:00', '18:15:00']),
            'amount' => fake()->randomElement([40, 45, 50, 55]),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
        ];
    }
}
