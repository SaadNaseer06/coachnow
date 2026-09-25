<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\CoachAvailabilitySlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CoachAvailabilityService
{
    /**
     * Expand weekly availability into bookable open slots between $from and $to (inclusive dates).
     *
     * @return list<array{
     *   date: string,
     *   time: string,
     *   time_label: string,
     *   end_time: string,
     *   duration_minutes: int,
     *   location_id: int,
     *   location_name: string,
     *   location_area: string|null,
     *   block_id: int
     * }>
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

        if ($blocks->isEmpty()) {
            return [];
        }

        $occupancy = Coach::occupancyByCoachIds([$coach->id])[$coach->id] ?? [];
        $busy = $this->busyWindows($occupancy);

        $slots = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dayOfWeek = (int) $cursor->dayOfWeek; // 0 Sun … 6 Sat
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

                    if ($slotStart->lt(now())) {
                        $slotStart->addMinutes($duration);
                        continue;
                    }

                    if (! $this->overlapsBusy($dateStr, $startKey, $duration, $busy)) {
                        $location = $block->location;
                        $slots[] = [
                            'date' => $dateStr,
                            'time' => $startKey,
                            'time_label' => $slotStart->format('g:i A'),
                            'end_time' => $slotEnd->format('H:i'),
                            'duration_minutes' => $duration,
                            'location_id' => (int) $block->location_id,
                            'location_name' => $location?->name ?? 'Field TBD',
                            'location_area' => $location?->area,
                            'block_id' => (int) $block->id,
                        ];
                    }

                    $slotStart->addMinutes($duration);
                }
            }

            $cursor->addDay();
        }

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
