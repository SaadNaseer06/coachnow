<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $extraAthletes = [
            ['name' => 'Jordan M.', 'email' => 'jordan@email.com'],
            ['name' => 'Ava S.', 'email' => 'ava@email.com'],
            ['name' => 'Diego R.', 'email' => 'diego@email.com'],
            ['name' => 'Noah K.', 'email' => 'noah@email.com'],
            ['name' => 'Mia L.', 'email' => 'mia@email.com'],
        ];

        foreach ($extraAthletes as $row) {
            User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_ATHLETE,
                    'phone' => '555-'.substr(md5($row['email']), 0, 4),
                ]
            );
        }

        $athletes = User::query()->where('role', User::ROLE_ATHLETE)->get()->keyBy('email');
        $coaches = Coach::query()->with('location')->where('status', 'active')->orderBy('id')->get();

        if ($coaches->isEmpty()) {
            return;
        }

        $samples = [
            ['athlete' => 'Jordan M.', 'email' => 'jordan@email.com', 'type' => 'Private', 'days' => 0, 'time' => '16:00:00', 'status' => 'confirmed'],
            ['athlete' => 'Ava S.', 'email' => 'ava@email.com', 'type' => 'Small Group', 'days' => 0, 'time' => '17:30:00', 'status' => 'pending'],
            ['athlete' => 'Diego R.', 'email' => 'diego@email.com', 'type' => 'Private', 'days' => 1, 'time' => '18:15:00', 'status' => 'confirmed'],
            ['athlete' => 'Noah K.', 'email' => 'noah@email.com', 'type' => 'Team', 'days' => 3, 'time' => '09:00:00', 'status' => 'confirmed'],
            ['athlete' => 'Mia L.', 'email' => 'mia@email.com', 'type' => 'Performance', 'days' => 4, 'time' => '10:00:00', 'status' => 'cancelled'],
            ['athlete' => 'Jamie Underwood', 'email' => 'player@coachnow.test', 'type' => 'Private', 'days' => 2, 'time' => '16:00:00', 'status' => 'confirmed'],
            ['athlete' => 'Jordan M.', 'email' => 'jordan@email.com', 'type' => 'Private', 'days' => 5, 'time' => '17:00:00', 'status' => 'confirmed'],
            ['athlete' => 'Ava S.', 'email' => 'ava@email.com', 'type' => 'Small Group', 'days' => 6, 'time' => '09:30:00', 'status' => 'pending'],
            ['athlete' => 'Diego R.', 'email' => 'diego@email.com', 'type' => 'Private', 'days' => 7, 'time' => '18:00:00', 'status' => 'confirmed'],
            ['athlete' => 'Noah K.', 'email' => 'noah@email.com', 'type' => 'Team', 'days' => 8, 'time' => '15:00:00', 'status' => 'confirmed'],
        ];

        foreach ($samples as $index => $sample) {
            $coach = $coaches[$index % $coaches->count()];
            $reference = 'CN-'.str_pad((string) (1042 - $index), 4, '0', STR_PAD_LEFT);
            $athlete = $athletes->get($sample['email']);

            Booking::query()->updateOrCreate(
                ['reference' => $reference],
                [
                    'coach_id' => $coach->id,
                    'athlete_id' => $athlete?->id,
                    'location_id' => $coach->location_id,
                    'athlete_name' => $sample['athlete'],
                    'session_type' => $sample['type'],
                    'session_date' => Carbon::today()->addDays($sample['days'])->toDateString(),
                    'session_time' => $sample['time'],
                    'amount' => $coach->rate,
                    'status' => $sample['status'],
                ]
            );
        }
    }
}
