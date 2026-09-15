<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\SessionRequestPlayer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SessionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $player = User::query()->where('email', 'player@coachnow.test')->first();
        $jordan = User::query()->where('email', 'jordan@email.com')->first();
        $diego = User::query()->where('email', 'diego@email.com')->first();
        $sommers = Location::query()->where('slug', 'sommers-bend')->first();
        $birdsall = Location::query()->where('slug', 'birdsall')->first();
        $coach = User::query()->where('email', 'coach@coachnow.test')->first()?->coach;

        $open = SessionRequest::query()->updateOrCreate(
            ['reference' => 'CN-2847'],
            [
                'requester_id' => $player?->id,
                'location_id' => $sommers?->id,
                'location_name' => $sommers?->name ?? 'Sommers Bend',
                'location_city' => $sommers?->area ?? 'Murrieta, CA',
                'session_date' => Carbon::today()->addDays(2)->toDateString(),
                'session_time' => '16:00:00',
                'session_type' => 'Speed Agility Quickness (SAQ) — Group Session',
                'sport' => 'Soccer',
                'age_range' => 'U10 (9–10 years)',
                'price_range' => '$50 – $100 / player',
                'player_level' => 'Intermediate',
                'notes' => 'Group SAQ session before weekend tournament.',
                'min_players' => 4,
                'max_players' => 8,
                'looking_for' => 7,
                'know_by_at' => Carbon::now()->addHours(6),
                'deposit_amount' => 10,
                'card_on_file' => 'Visa ···· 4242',
                'status' => 'open',
            ]
        );

        SessionRequestPlayer::query()->updateOrCreate(
            ['session_request_id' => $open->id, 'role' => 'requester'],
            [
                'user_id' => $player?->id,
                'name' => $player?->name ?? 'Jamie Underwood',
                'initials' => 'JU',
                'paid' => false,
                'card_on_file' => 'Visa ···· 4242',
            ]
        );

        $open2 = SessionRequest::query()->updateOrCreate(
            ['reference' => 'CN-2812'],
            [
                'requester_id' => $jordan?->id,
                'location_id' => $birdsall?->id,
                'location_name' => $birdsall?->name ?? 'Birdsall',
                'location_city' => $birdsall?->area ?? 'Temecula, CA',
                'session_date' => Carbon::today()->addDays(4)->toDateString(),
                'session_time' => '17:30:00',
                'session_type' => 'Skills Training — Group Session',
                'sport' => 'Soccer',
                'age_range' => 'U12 (11–12 years)',
                'price_range' => '$25 – $50 / player',
                'player_level' => 'Beginner',
                'notes' => 'First touch and passing under pressure.',
                'min_players' => null,
                'max_players' => 6,
                'looking_for' => 5,
                'know_by_at' => Carbon::now()->addDay(),
                'deposit_amount' => 10,
                'card_on_file' => 'Mastercard ···· 4444',
                'status' => 'open',
            ]
        );

        SessionRequestPlayer::query()->updateOrCreate(
            ['session_request_id' => $open2->id, 'role' => 'requester'],
            [
                'user_id' => $jordan?->id,
                'name' => $jordan?->name ?? 'Jordan M.',
                'initials' => 'JM',
                'paid' => false,
                'card_on_file' => 'Mastercard ···· 4444',
            ]
        );

        $hosted = SessionRequest::query()->updateOrCreate(
            ['reference' => 'CN-2798'],
            [
                'requester_id' => $diego?->id,
                'location_id' => $sommers?->id,
                'location_name' => $sommers?->name ?? 'Sommers Bend',
                'location_city' => $sommers?->area ?? 'Murrieta, CA',
                'session_date' => Carbon::today()->addDays(6)->toDateString(),
                'session_time' => '09:00:00',
                'session_type' => 'Technical Development — Group Session',
                'sport' => 'Soccer',
                'age_range' => 'U14 (13–14 years)',
                'price_range' => '$50 – $100 / player',
                'player_level' => 'Advanced',
                'notes' => 'Open to other U14 players joining.',
                'min_players' => 3,
                'max_players' => 5,
                'looking_for' => 2,
                'know_by_at' => Carbon::now()->addDays(5),
                'deposit_amount' => 10,
                'card_on_file' => 'Visa ···· 4242',
                'status' => 'hosted',
                'host_coach_id' => $coach?->id,
                'coach_note' => 'Looking for 2 more U14 players.',
                'accepted_at' => now(),
            ]
        );

        SessionRequestPlayer::query()->updateOrCreate(
            ['session_request_id' => $hosted->id, 'user_id' => $diego?->id],
            [
                'name' => $diego?->name ?? 'Diego R.',
                'initials' => 'DR',
                'role' => 'requester',
                'paid' => true,
                'paid_with' => 'Visa ···· 4242',
                'card_on_file' => 'Visa ···· 4242',
            ]
        );

        SessionRequestPlayer::query()->updateOrCreate(
            ['session_request_id' => $hosted->id, 'name' => 'Alex Rivera'],
            [
                'user_id' => null,
                'initials' => 'AR',
                'role' => 'joiner',
                'paid' => true,
                'paid_with' => 'Mastercard ···· 4444',
            ]
        );

        SessionRequestPlayer::query()->updateOrCreate(
            ['session_request_id' => $hosted->id, 'name' => 'Sam Park'],
            [
                'user_id' => null,
                'initials' => 'SP',
                'role' => 'joiner',
                'paid' => false,
                'paid_with' => null,
            ]
        );
    }
}
