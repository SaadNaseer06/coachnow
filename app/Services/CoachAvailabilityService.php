<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\CoachAvailabilitySlot;
use App\Models\CoachGroupSession;
use Illuminate\Support\Carbon;

class CoachAvailabilityService
{
    /**
     * Expand weekly availability into bookable open slots between $from and $to (inclusive dates).
     *
     * @return list<array<string, mixed>>
     */
    public function openSlotsForCoach(Coach $coach, Carbon $from, Carbon $to, int $durationMinutes = 60): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        if ($to->lt($from)) {
            return [];
        }

        $blocks = CoachAvailabilitySlot::query()
            ->with('location')
            ->where('coach_id', $coach->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $occupancy = Coach::occupancyByCoachIds([$coach->id])[$coach->id] ?? [];
        $busy = $this->busyWindows($occupancy);

        $groups = CoachGroupSession::query()
            ->with('location')
            ->where('coach_id', $coach->id)
            ->whereIn('status', ['open', 'full'])
            ->whereDate('session_date', '>=', $from->toDateString())
            ->whereDate('session_date', '<=', $to->toDateString())
            ->get();

        $groupsByKey = [];
        foreach ($groups as $group) {
            $key = $group->session_date?->toDateString().'|'.substr((string) $group->session_time, 0, 5);
            $groupsByKey[$key] = $group;
        }

        $slots = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dayOfWeek = (int) $cursor->dayOfWeek;
            $dateStr = $cursor->toDateString();

            foreach ($blocks->where('day_of_week', $dayOfWeek) as $block) {
                $duration = max(30, (int) ($block->duration_minutes ?: $durationMinutes));
                $windowStart = Carbon::parse($dateStr.' '.$this->normalizeTime($block->start_time));
                $windowEnd = Carbon::parse($dateStr.' '.$this->normalizeTime($block->end_time));

                if ($windowEnd->lte($windowStart)) {
                    continue;
                }

                $slotStart = $windowStart->copy();
                while ($slotStart->copy()->addMinutes($duration)->lte($windowEnd)) {
                    $slotEnd = $slotStart->copy()->addMinutes($duration);
                    $startKey = $slotStart->format('H:i');
                    $groupKey = $dateStr.'|'.$startKey;
                    $group = $groupsByKey[$groupKey] ?? null;

                    if ($slotStart->lt(now())) {
                        $slotStart->addMinutes($duration);
                        continue;
                    }

                    $busyHit = $this->overlapsBusy($dateStr, $startKey, $duration, $busy);
                    if ($busyHit && ! $group) {
                        $slotStart->addMinutes($duration);
                        continue;
                    }

                    $location = $group?->location ?: $block->location;
                    $slot = [
                        'date' => $dateStr,
                        'time' => $startKey,
                        'time_label' => $slotStart->format('g:i A'),
                        'end_time' => $slotEnd->format('H:i'),
                        'duration_minutes' => $group ? $group->durationMinutes() : $duration,
                        'location_id' => (int) ($group?->location_id ?: $block->location_id),
                        'location_name' => $location?->name ?? 'Field TBD',
                        'location_area' => $location?->area,
                        'block_id' => (int) $block->id,
                        'group_session_id' => null,
                        'session_type' => null,
                        'booked_count' => null,
                        'max_players' => null,
                        'spots_remaining' => null,
                        'label' => null,
                        'joinable' => false,
                    ];

                    if ($group) {
                        // Private groups are reserved — hide from other families.
                        if (! $group->is_public) {
                            $slotStart->addMinutes($duration);
                            continue;
                        }

                        $meta = $group->toParentSlotArray();
                        $slot = array_merge($slot, $meta);
                        $slot['time_label'] = $meta['full']
                            ? $slotStart->format('g:i A').' · Full'
                            : $slotStart->format('g:i A').' · Open group';
                    }

                    $slots[] = $slot;
                    $slotStart->addMinutes($duration);
                }
            }

            $cursor->addDay();
        }

        // Public groups booked outside the weekly availability blocks still need to show.
        $emitted = array_filter(array_column($slots, 'group_session_id'));
        foreach ($groups as $group) {
            if (! $group->is_public || in_array($group->id, $emitted, true)) {
                continue;
            }

            $start = Carbon::parse($group->session_date->toDateString().' '.$this->normalizeTime($group->session_time));
            if ($start->lt(now())) {
                continue;
            }

            $meta = $group->toParentSlotArray();
            $slots[] = array_merge([
                'date' => $start->toDateString(),
                'time' => $start->format('H:i'),
                'end_time' => $start->copy()->addMinutes($group->durationMinutes())->format('H:i'),
                'duration_minutes' => $group->durationMinutes(),
                'location_id' => (int) $group->location_id,
                'location_name' => $group->location?->name ?? 'Field TBD',
                'location_area' => $group->location?->area,
                'block_id' => null,
            ], $meta, [
                'time_label' => $start->format('g:i A').($meta['full'] ? ' · Full' : ' · Open group'),
            ]);
        }

        usort($slots, fn (array $a, array $b) => [$a['date'], $a['time']] <=> [$b['date'], $b['time']]);

        return $slots;
    }

    /**
     * @param  list<array{date:string,time:?string,minutes:int}>  $occupancy
     * @return list<array{date:string,start:int,end:int}>
     */
    private function busyWindows(array $occupancy): array
    {
        $windows = [];
        foreach ($occupancy as $row) {
            $date = $row['date'] ?? null;
            if (! $date) {
                continue;
            }
            $time = $row['time'] ?? '00:00';
            $minutes = max(30, (int) ($row['minutes'] ?? 60));
            $start = $this->timeToMinutes(substr((string) $time, 0, 5));
            $windows[] = [
                'date' => $date,
                'start' => $start,
                'end' => $start + $minutes,
            ];
        }

        return $windows;
    }

    /**
     * @param  list<array{date:string,start:int,end:int}>  $busy
     */
    private function overlapsBusy(string $date, string $timeHi, int $duration, array $busy): bool
    {
        $start = $this->timeToMinutes($timeHi);
        $end = $start + $duration;

        foreach ($busy as $window) {
            if ($window['date'] !== $date) {
                continue;
            }
            if ($start < $window['end'] && $end > $window['start']) {
                return true;
            }
        }

        return false;
    }

    private function timeToMinutes(string $hi): int
    {
        [$h, $m] = array_pad(explode(':', $hi), 2, 0);

        return ((int) $h * 60) + (int) $m;
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $raw = (string) $time;
        if (preg_match('/^\d{2}:\d{2}$/', $raw)) {
            return $raw.':00';
        }

        return $raw;
    }
}
