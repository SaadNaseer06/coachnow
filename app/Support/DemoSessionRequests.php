<?php

namespace App\Support;

class DemoSessionRequests
{
    /** Open session requests from Request Session flow (static demo data). */
    public static function all(): array
    {
        return [
            [
                'id' => 'CN-2847',
                'initials' => 'JU',
                'name' => 'Jamie Underwood',
                'location' => 'Sommers Bend',
                'city' => 'Murrieta, CA',
                'when' => 'Tue, Sep 9 · 4:00 PM',
                'session_type' => 'Speed Agility Quickness (SAQ) — Group Session',
                'age_range' => 'U10 (9–10 years)',
                'price_range' => '$50 – $100',
                'sport' => 'Soccer',
                'notes' => 'Group SAQ session before weekend tournament.',
                'status' => 'open',
                'accept_seconds' => 847,
                'posted' => '2 min ago',
            ],
            [
                'id' => 'CN-2812',
                'initials' => 'MR',
                'name' => 'Mia Rodriguez',
                'location' => 'Winchester Sports Park',
                'city' => 'Winchester, CA',
                'when' => 'Thu, Sep 11 · 5:30 PM',
                'session_type' => 'Skills Training — Group Session',
                'age_range' => 'U12 (11–12 years)',
                'price_range' => '$25 – $50',
                'sport' => 'Soccer',
                'notes' => 'First touch and passing under pressure.',
                'status' => 'open',
                'accept_seconds' => 612,
                'posted' => '6 min ago',
            ],
            [
                'id' => 'CN-2798',
                'initials' => 'DK',
                'name' => 'Daniel Keller',
                'location' => 'Bear Creek Park',
                'city' => 'Murrieta, CA',
                'when' => 'Sat, Sep 13 · 9:00 AM',
                'session_type' => 'Technical Development — Group Session',
                'age_range' => 'U14 (13–14 years)',
                'price_range' => '$50 – $100',
                'sport' => 'Soccer',
                'notes' => 'Open to other U14 players joining.',
                'status' => 'accepted',
                'accept_seconds' => 0,
                'posted' => '18 min ago',
                'accepted_by' => 'You',
                'players_joined' => 2,
            ],
        ];
    }

    public static function openCount(): int
    {
        return collect(static::all())->where('status', 'open')->count();
    }
}
