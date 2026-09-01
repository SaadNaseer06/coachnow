<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;

class CoachController extends Controller
{
    /** Calendar spans 6:00 AM – 9:00 PM in 30 minute rows. */
    private const CAL_START_HOUR = 6;

    private const CAL_END_HOUR = 21;

    public function schedule()
    {
        return view('coach.schedule', [
            'weekLabel' => 'Aug 24 – Aug 30, 2026',
            'hours' => range(self::CAL_START_HOUR, self::CAL_END_HOUR - 1),
            'days' => $this->calendarDays(),
            'sessions' => $this->scheduleSessions(),
            'summary' => [
                ['label' => 'Sessions', 'value' => '11', 'note' => 'Booked this week'],
                ['label' => 'Players', 'value' => '8', 'note' => 'Across all sessions'],
                ['label' => 'Hours', 'value' => '12.5', 'note' => 'On the field'],
            ],
            'requests' => $this->bookingRequests(),
        ]);
    }

    public function dashboard()
    {
        return view('coach.dashboard', [
            'players' => $this->roster(),
            'today' => $this->todaySessions(),
            'requests' => $this->bookingRequests(),
        ]);
    }

    public function playerOverview()
    {
        return view('coach.player-overview', [
            'players' => $this->roster(),
        ]);
    }

    public function playerShow(string $player)
    {
        $profile = $this->findPlayer($player);

        if (! $profile) {
            abort(404);
        }

        return view('coach.player-show', [
            'player' => $profile,
            'skills' => $this->skills(),
            'sessions' => $this->recentSessions(),
            'goals' => $this->goals(),
            'notes' => $this->notes(),
            'videos' => $this->sharedVideos(),
        ]);
    }

    public function addReport()
    {
        $slug = request('player', 'jamie-smith');
        $profile = $this->findPlayer($slug) ?? $this->findPlayer('jamie-smith');

        return view('coach.add-report', [
            'player' => $profile,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Demo data                                                           */
    /* ------------------------------------------------------------------ */

    private function calendarDays(): array
    {
        return [
            ['name' => 'MON', 'num' => '24'],
            ['name' => 'TUE', 'num' => '25'],
            ['name' => 'WED', 'num' => '26', 'today' => true],
            ['name' => 'THU', 'num' => '27'],
            ['name' => 'FRI', 'num' => '28'],
            ['name' => 'SAT', 'num' => '29'],
            ['name' => 'SUN', 'num' => '30'],
        ];
    }

    private function scheduleSessions(): array
    {
        $sessions = [
            ['day' => 0, 'start' => '8:30', 'duration' => 60, 'title' => 'Jamie Smith', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 1, 'start' => '10:00', 'duration' => 60, 'title' => 'Nicole Turner', 'type' => 'Small Group', 'players' => 3, 'tone' => 'yellow'],
            ['day' => 1, 'start' => '16:00', 'duration' => 60, 'title' => 'Alex Parker', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 2, 'start' => '9:00', 'duration' => 60, 'title' => 'Mia Lawson', 'type' => 'Small Group', 'players' => 2, 'tone' => 'yellow'],
            ['day' => 2, 'start' => '11:00', 'duration' => 90, 'title' => 'Team Training', 'type' => 'Group Session', 'players' => 8, 'tone' => 'purple'],
            ['day' => 2, 'start' => '14:00', 'duration' => 60, 'title' => 'Jake Miller', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 2, 'start' => '16:30', 'duration' => 60, 'title' => 'Ryan Keller', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 2, 'start' => '18:00', 'duration' => 45, 'title' => 'Sam Doyle', 'type' => 'Assessment', 'players' => 1, 'tone' => 'orange'],
            ['day' => 3, 'start' => '15:00', 'duration' => 60, 'title' => 'Speed & Agility', 'type' => 'Group Session', 'players' => 6, 'tone' => 'purple'],
            ['day' => 4, 'start' => '9:30', 'duration' => 60, 'title' => 'Jamie Smith', 'type' => 'Private Session', 'players' => 1, 'tone' => 'green'],
            ['day' => 4, 'start' => '17:00', 'duration' => 60, 'title' => 'Nicole Turner', 'type' => 'Small Group', 'players' => 2, 'tone' => 'yellow'],
            ['day' => 5, 'start' => '6:00', 'duration' => 900, 'title' => 'Tournament', 'type' => 'All day · Blocked', 'players' => 0, 'tone' => 'blocked', 'allDay' => true],
            ['day' => 6, 'start' => '6:00', 'duration' => 900, 'title' => 'Unavailable', 'type' => 'Blocked', 'players' => 0, 'tone' => 'blocked', 'allDay' => true],
        ];

        $rows = (self::CAL_END_HOUR - self::CAL_START_HOUR) * 2;

        return array_map(function (array $session) use ($rows) {
            if (! empty($session['allDay'])) {
                $session['gridStart'] = 1;
                $session['gridEnd'] = $rows + 1;

                return $session;
            }

            [$hours, $minutes] = array_map('intval', explode(':', $session['start']));
            $offset = ($hours * 60 + $minutes) - (self::CAL_START_HOUR * 60);

            $start = max(1, (int) floor($offset / 30) + 1);
            $span = max(1, (int) ceil($session['duration'] / 30));

            $session['gridStart'] = $start;
            $session['gridEnd'] = min($rows + 1, $start + $span);

            return $session;
        }, $sessions);
    }

    private function bookingRequests(): array
    {
        return [
            ['name' => 'Ella Rodriguez', 'initials' => 'ER', 'when' => 'Sat, Aug 30 · 12:00 PM', 'type' => 'Small Group', 'note' => 'U12 · First touch focus'],
            ['name' => 'Sam Doyle', 'initials' => 'SD', 'when' => 'Tue, Sep 2 · 5:30 PM', 'type' => 'Private Session', 'note' => 'U14 · Finishing'],
        ];
    }

    private function todaySessions(): array
    {
        return [
            ['time' => '9:00 AM', 'name' => 'Mia Lawson', 'type' => 'Small Group', 'tone' => 'yellow'],
            ['time' => '11:00 AM', 'name' => 'Team Training', 'type' => 'Group Session', 'tone' => 'purple'],
            ['time' => '2:00 PM', 'name' => 'Jake Miller', 'type' => 'Private Session', 'tone' => 'green'],
            ['time' => '4:30 PM', 'name' => 'Ryan Keller', 'type' => 'Private Session', 'tone' => 'green'],
        ];
    }

    private function roster(): array
    {
        return [
            ['slug' => 'jamie-smith', 'name' => 'Jamie Smith', 'initials' => 'JS', 'age' => 'U14', 'sport' => 'Soccer', 'sessions' => 12, 'focus' => 'Scanning', 'next' => 'Sat, Aug 30 · 4:00 PM', 'reportDue' => true],
            ['slug' => 'ella-rodriguez', 'name' => 'Ella Rodriguez', 'initials' => 'ER', 'age' => 'U12', 'sport' => 'Soccer', 'sessions' => 8, 'focus' => 'First touch', 'next' => 'Mon, Sep 1 · 5:30 PM', 'reportDue' => false],
            ['slug' => 'alex-parker', 'name' => 'Alex Parker', 'initials' => 'AP', 'age' => 'U16', 'sport' => 'Soccer', 'sessions' => 15, 'focus' => 'Finishing', 'next' => 'Wed, Sep 3 · 6:00 PM', 'reportDue' => true],
            ['slug' => 'nicole-turner', 'name' => 'Nicole Turner', 'initials' => 'NT', 'age' => 'U13', 'sport' => 'Soccer', 'sessions' => 6, 'focus' => 'Passing', 'next' => 'Fri, Sep 5 · 5:00 PM', 'reportDue' => false],
        ];
    }

    private function findPlayer(string $slug): ?array
    {
        $roster = collect($this->roster())->firstWhere('slug', $slug);

        if (! $roster) {
            return null;
        }

        $defaults = $this->playerDefaults();

        return array_merge($defaults, [
            'slug' => $roster['slug'],
            'name' => $roster['name'],
            'initials' => $roster['initials'],
            'age' => $roster['age'],
            'sport' => $roster['sport'],
            'total_sessions' => $roster['sessions'],
            'current_focus' => $slug === 'jamie-smith'
                ? $defaults['current_focus']
                : 'Build on ' . strtolower($roster['focus']) . ' with consistent reps in training and at home.',
            'next_session' => $roster['next'] . ' · Field A',
            'reportDue' => $roster['reportDue'],
        ]);
    }

    private function playerDefaults(): array
    {
        return [
            'member_since' => 'Jun 2024',
            'plan' => 'Development Plus',
            'plan_status' => 'Active',
            'plan_renews' => 'Aug 20, 2026',
            'total_sessions' => 12,
            'streak' => '4 weeks',
            'progress' => 'Improving',
            'last_session' => 'Aug 4, 2026',
            'current_focus' => 'Scan before receiving and open the body to play forward.',
            'next_session' => 'Sat, Aug 30 · 4:00 PM · Field A',
        ];
    }

    private function skills(): array
    {
        return [
            ['name' => 'First Touch', 'status' => 'Strong', 'level' => 3, 'icon' => 'ball'],
            ['name' => 'Scanning', 'status' => 'Developing', 'level' => 2, 'icon' => 'eye'],
            ['name' => 'Passing', 'status' => 'Strong', 'level' => 3, 'icon' => 'share'],
            ['name' => 'Finishing', 'status' => 'Developing', 'level' => 2, 'icon' => 'target'],
            ['name' => 'Confidence', 'status' => 'Strong', 'level' => 3, 'icon' => 'heart'],
            ['name' => 'Work Rate', 'status' => 'Excellent', 'level' => 4, 'icon' => 'bolt'],
        ];
    }

    private function recentSessions(): array
    {
        return [
            ['date' => 'Aug 4, 2026', 'type' => 'Private · 60 min', 'focus' => 'Scanning, First touch', 'rating' => 3, 'summary' => 'Good progress checking shoulders before receiving.'],
            ['date' => 'Jul 28, 2026', 'type' => 'Small group · 90 min', 'focus' => 'Passing under pressure', 'rating' => 3, 'summary' => 'Strong weight on passes, held the ball a beat too long.'],
            ['date' => 'Jul 19, 2026', 'type' => 'Private · 60 min', 'focus' => 'Finishing, Movement', 'rating' => 2, 'summary' => 'Needs more composure on the final touch in the box.'],
        ];
    }

    private function goals(): array
    {
        return [
            ['text' => 'Improve weak foot passing', 'done' => false],
            ['text' => 'Be more confident in 1v1 situations', 'done' => true],
            ['text' => 'Better movement off the ball', 'done' => false],
        ];
    }

    private function notes(): array
    {
        return [
            ['author' => 'Coach Lee', 'date' => 'Aug 4, 2026', 'text' => 'Great awareness and scanning before receiving. Excellent first touch under pressure. Focus next time: right shoulder drops when passing under pressure — work on keeping head up.'],
            ['author' => 'Coach Lee', 'date' => 'Jul 28, 2026', 'text' => 'Jamie is responding well to the scanning cues. Keep reinforcing the habit in small-sided games.'],
        ];
    }

    private function sharedVideos(): array
    {
        return [
            ['title' => 'Scan before receiving', 'meta' => '3-min guide · Shared Aug 4'],
            ['title' => 'Back-foot first touch', 'meta' => 'Wall drill · Shared Jul 28'],
            ['title' => 'Composure in the box', 'meta' => '4-min guide · Shared Jul 19'],
        ];
    }
}
