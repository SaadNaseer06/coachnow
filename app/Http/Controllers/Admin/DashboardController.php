<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function coaches()
    {
        return view('admin.coaches');
    }

    public function bookings()
    {
        return view('admin.bookings');
    }

    public function locations()
    {
        return view('admin.locations');
    }

    public function athletes()
    {
        return view('admin.athletes');
    }

    public function schedule()
    {
        return view('admin.schedule', [
            'weekLabel' => 'Aug 24 – Aug 30, 2026',
            'sessions' => $this->demoScheduleSessions(),
        ]);
    }

    private function timeToGrid(string $time, int $duration): array
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        $startMinutes = ($hours * 60 + $minutes) - (4 * 60);
        $gridStart = max(1, (int) floor($startMinutes / 30) + 1);
        $gridSpan = max(1, (int) ceil($duration / 30));

        return ['start' => $gridStart, 'end' => $gridStart + $gridSpan];
    }

    private function demoScheduleSessions(): array
    {
        $sessions = [
            ['day' => 0, 'start' => '8:30', 'duration' => 60, 'title' => 'Ella R.', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 1, 'start' => '10:00', 'duration' => 60, 'title' => 'Nicole T.', 'type' => 'Small Group (2-3)', 'players' => 3, 'tone' => 'yellow'],
            ['day' => 1, 'start' => '16:00', 'duration' => 60, 'title' => 'Alex P.', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 2, 'start' => '9:00', 'duration' => 60, 'title' => 'Mia L.', 'type' => 'Small Group (2-3)', 'players' => 2, 'tone' => 'yellow'],
            ['day' => 2, 'start' => '11:00', 'duration' => 90, 'title' => 'Team Training', 'type' => 'Group Session', 'players' => 8, 'tone' => 'purple'],
            ['day' => 2, 'start' => '14:00', 'duration' => 60, 'title' => 'Jake M.', 'type' => 'Private Session', 'players' => 1, 'tone' => 'blue'],
            ['day' => 2, 'start' => '16:30', 'duration' => 60, 'title' => 'Ryan K.', 'type' => 'Private Session', 'players' => 1, 'tone' => 'blue'],
            ['day' => 2, 'start' => '18:00', 'duration' => 45, 'title' => 'Sam D.', 'type' => 'Assessment', 'players' => 1, 'tone' => 'orange'],
            ['day' => 3, 'start' => '15:00', 'duration' => 60, 'title' => 'Speed & Agility', 'type' => 'Group Session', 'players' => 6, 'tone' => 'purple'],
            ['day' => 4, 'start' => '9:30', 'duration' => 60, 'title' => 'Ella R.', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 4, 'start' => '17:00', 'duration' => 60, 'title' => 'Nicole T.', 'type' => 'Small Group (2-3)', 'players' => 2, 'tone' => 'yellow'],
            ['day' => 5, 'start' => '0:00', 'duration' => 1440, 'title' => 'Tournament', 'type' => 'All Day · Blocked', 'players' => 0, 'tone' => 'blocked', 'allDay' => true],
            ['day' => 6, 'start' => '0:00', 'duration' => 1440, 'title' => 'Unavailable', 'type' => 'Blocked', 'players' => 0, 'tone' => 'blocked', 'allDay' => true],
        ];

        return array_map(function (array $session) {
            if (! empty($session['allDay'])) {
                $session['gridStart'] = 1;
                $session['gridEnd'] = 35;

                return $session;
            }

            $grid = $this->timeToGrid($session['start'], $session['duration']);
            $session['gridStart'] = $grid['start'];
            $session['gridEnd'] = $grid['end'];

            return $session;
        }, $sessions);
    }
}
