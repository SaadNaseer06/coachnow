<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@coachnow.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'phone' => '555-0100',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'coach@coachnow.test'],
            [
                'name' => 'Coach Lee',
                'password' => Hash::make('password'),
                'role' => User::ROLE_COACH,
                'phone' => '555-0101',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'player@coachnow.test'],
            [
                'name' => 'Jamie Underwood',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ATHLETE,
                'phone' => '555-0102',
            ]
        );
    }
}
