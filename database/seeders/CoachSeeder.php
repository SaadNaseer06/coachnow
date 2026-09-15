<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoachSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'sommers-bend' => [
                ['name' => 'Coach Lee', 'email' => 'coach@coachnow.test', 'specialty' => 'Private Soccer Training', 'ages' => 'Ages 8–14', 'rate' => 50, 'rating' => 4.9, 'reviews' => 86, 'photo' => 'assets/Rectangle 8.png', 'experience' => '6-7 years'],
                ['name' => 'Coach Heidi', 'email' => 'heidi@coachnow.test', 'specialty' => 'Small Group Soccer', 'ages' => 'Ages 6–12', 'rate' => 40, 'rating' => 5.0, 'reviews' => 64, 'photo' => 'assets/Rectangle 8-2.png', 'experience' => '4-5 years'],
                ['name' => 'Coach Gabe', 'email' => 'gabe@coachnow.test', 'specialty' => 'Team Training', 'ages' => 'All ages', 'rate' => 45, 'rating' => 4.8, 'reviews' => 112, 'photo' => 'assets/Rectangle 8-1.png', 'experience' => '8+ years'],
            ],
            'birdsall' => [
                ['name' => 'Coach Alex', 'email' => 'alex@coachnow.test', 'specialty' => 'Private Soccer Training', 'ages' => 'Ages 8–14', 'rate' => 45, 'rating' => 4.9, 'reviews' => 128, 'photo' => 'assets/Rectangle 8-1.png', 'experience' => '6-7 years'],
                ['name' => 'Coach Maria', 'email' => 'maria@coachnow.test', 'specialty' => 'Small Group Soccer', 'ages' => 'Ages 8–14', 'rate' => 45, 'rating' => 4.9, 'reviews' => 98, 'photo' => 'assets/Rectangle 8-2.png', 'experience' => '4-5 years'],
            ],
            'los-alamos' => [
                ['name' => 'Coach Davos', 'email' => 'davos@coachnow.test', 'specialty' => 'Private Soccer Training', 'ages' => 'Ages 10–16', 'rate' => 55, 'rating' => 4.9, 'reviews' => 74, 'photo' => 'assets/Rectangle 8.png', 'experience' => '8+ years'],
                ['name' => 'Coach Ceja', 'email' => 'ceja@coachnow.test', 'specialty' => 'Performance & Speed', 'ages' => 'Ages 12–18', 'rate' => 50, 'rating' => 4.8, 'reviews' => 51, 'photo' => 'assets/Rectangle 8-1.png', 'experience' => '4-5 years'],
            ],
            'alta-murrieta' => [
                ['name' => 'Coach Mike', 'email' => 'mike@coachnow.test', 'specialty' => 'Team Training', 'ages' => 'Advanced Players', 'rate' => 45, 'rating' => 4.9, 'reviews' => 128, 'photo' => 'assets/Rectangle 8.png', 'experience' => '6-7 years'],
                ['name' => 'Coach Jordan', 'email' => 'jordan.coach@coachnow.test', 'specialty' => '1-on-1 Skills', 'ages' => 'Ages 7–13', 'rate' => 42, 'rating' => 4.7, 'reviews' => 39, 'photo' => 'assets/Rectangle 8-2.png', 'experience' => '1-3 years'],
            ],
            'temecula-sports' => [
                ['name' => 'Coach Sam', 'email' => 'sam@coachnow.test', 'specialty' => 'Small Group Soccer', 'ages' => 'Ages 5–10', 'rate' => 38, 'rating' => 4.8, 'reviews' => 67, 'photo' => 'assets/Rectangle 8-1.png', 'experience' => '4-5 years'],
                ['name' => 'Coach Riley', 'email' => 'riley@coachnow.test', 'specialty' => 'Private Soccer Training', 'ages' => 'Ages 9–15', 'rate' => 48, 'rating' => 5.0, 'reviews' => 91, 'photo' => 'assets/Rectangle 8.png', 'experience' => '6-7 years'],
                ['name' => 'Coach Pat', 'email' => 'pat@coachnow.test', 'specialty' => 'Clinics & Camps', 'ages' => 'All ages', 'rate' => 35, 'rating' => 4.6, 'reviews' => 44, 'photo' => 'assets/Rectangle 8-2.png', 'experience' => '1-3 years'],
            ],
            'california-oaks' => [
                ['name' => 'Coach Nina', 'email' => 'nina@coachnow.test', 'specialty' => 'Youth Development', 'ages' => 'Ages 6–11', 'rate' => 40, 'rating' => 4.9, 'reviews' => 58, 'photo' => 'assets/Rectangle 8-2.png', 'experience' => '4-5 years'],
                ['name' => 'Coach Omar', 'email' => 'omar@coachnow.test', 'specialty' => 'Private Soccer Training', 'ages' => 'Ages 11–17', 'rate' => 52, 'rating' => 4.8, 'reviews' => 73, 'photo' => 'assets/Rectangle 8-1.png', 'experience' => '6-7 years'],
            ],
        ];

        foreach ($catalog as $slug => $coaches) {
            $location = Location::query()->where('slug', $slug)->first();
            if (! $location) {
                continue;
            }

            foreach ($coaches as $row) {
                $user = User::query()->updateOrCreate(
                    ['email' => $row['email']],
                    [
                        'name' => $row['name'],
                        'password' => Hash::make('password'),
                        'role' => User::ROLE_COACH,
                        'phone' => '555-'.substr(md5($row['email']), 0, 4),
                    ]
                );

                Coach::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'location_id' => $location->id,
                        'display_name' => $row['name'],
                        'specialty' => $row['specialty'],
                        'experience' => $row['experience'],
                        'ages' => $row['ages'],
                        'status' => 'active',
                        'rate' => $row['rate'],
                        'rating' => $row['rating'],
                        'reviews_count' => $row['reviews'],
                        'photo_path' => $row['photo'],
                        'bio' => $row['name'].' trains local athletes near '.$location->name.'.',
                    ]
                );
            }
        }
    }
}
