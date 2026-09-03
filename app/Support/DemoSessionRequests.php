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
                'min_players' => 4,
                'max_players' => 8,
                'player_level' => 'Intermediate',
                'know_by' => 'Tue, Sep 9 · 12:00 PM',
                'deposit' => 10,
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
                'min_players' => '',
                'max_players' => 6,
                'player_level' => 'Beginner',
                'know_by' => 'Wed, Sep 10 · 8:00 PM',
                'deposit' => 10,
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
                'min_players' => 3,
                'max_players' => 5,
                'player_level' => 'Advanced',
                'know_by' => 'Fri, Sep 12 · 6:00 PM',
                'deposit' => 10,
                'status' => 'hosted',
                'deposit_paid' => true,
                'accept_seconds' => 0,
                'posted' => '18 min ago',
                'accepted_by' => 'You',
                'players_joined' => 3,
                'looking_for' => 2,
                'coach_note' => 'Looking for 2 more U14 players.',
            ],
        ];
    }

    public static function openCount(): int
    {
        return collect(static::all())
            ->filter(fn ($r) => in_array($r['status'] ?? '', ['open', 'hosted', 'awaiting_deposit', 'confirmed', 'accepted'], true))
            ->count();
    }
}
