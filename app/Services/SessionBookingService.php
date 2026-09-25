<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\SessionRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SessionBookingService
{
    public function createFromAcceptedSession(SessionRequest $session, Coach $coach): void
    {
        $session->loadMissing(['players', 'requester']);

        $amount = $session->deposit_amount !== null
            ? (float) $session->deposit_amount
            : (float) ($coach->rate ?? 0);

        $players = $session->players;
        if ($players->isEmpty() && $session->requester_id) {
            $players = collect([(object) [
                'user_id' => $session->requester_id,
                'name' => $session->requester?->name ?? 'Player',
            ]]);
        }

        foreach ($players as $player) {
            $athleteId = $player->user_id ?? null;
            $athleteName = $player->name ?? 'Player';

            $existing = Booking::query()
                ->where('session_request_id', $session->id)
                ->when(
                    $athleteId,
                    fn ($q) => $q->where('athlete_id', $athleteId),
                    fn ($q) => $q->whereNull('athlete_id')->where('athlete_name', $athleteName)
                )
                ->first();

            $payload = [
                'coach_id' => $coach->id,
                'athlete_id' => $athleteId,
                'location_id' => $session->location_id ?? $coach->location_id,
                'athlete_name' => $athleteName,
                'session_type' => $session->session_type ?? 'Private',
                'session_date' => $session->session_date?->toDateString(),
                'session_time' => $session->session_time,
                'duration_minutes' => 60,
                'amount' => $amount,
                'status' => 'confirmed',
                'session_request_id' => $session->id,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Booking::query()->create(array_merge($payload, [
                    'reference' => Booking::generateReference(),
                ]));
            }
        }
    }

    /**
     * Instant athlete booking against a published availability slot.
     *
     * @param  array{
     *   date: string,
     *   time: string,
     *   location_id: int,
     *   duration_minutes?: int,
     *   session_type?: string
     * }  $slot
     */
    public function createDirectBooking(Coach $coach, \App\Models\User $athlete, array $slot): Booking
    {
        $date = $slot['date'];
        $time = substr((string) $slot['time'], 0, 5);
        $duration = max(30, (int) ($slot['duration_minutes'] ?? 60));
        $locationId = (int) $slot['location_id'];

        $open = app(CoachAvailabilityService::class)
            ->openSlotsForCoach($coach, Carbon::parse($date), Carbon::parse($date), $duration);

        $match = collect($open)->first(function (array $row) use ($date, $time, $locationId) {
            return $row['date'] === $date
                && $row['time'] === $time
                && (int) $row['location_id'] === $locationId;
        });

        if (! $match) {
            throw ValidationException::withMessages([
                'slot' => 'That time is no longer available. Pick another slot.',
            ]);
        }

        return Booking::query()->create([
            'reference' => Booking::generateReference(),
            'session_request_id' => null,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'location_id' => $locationId,
            'athlete_name' => $athlete->name,
            'session_type' => $slot['session_type'] ?? 'Private 1-on-1',
            'session_date' => $date,
            'session_time' => $time,
            'duration_minutes' => (int) ($match['duration_minutes'] ?? $duration),
            'amount' => (float) ($coach->rate ?? 0),
            'status' => 'confirmed',
        ]);
    }

    /**
     * Coach-initiated booking (does not require published availability).
     *
     * @param  array{
     *   athlete_id?: int|null,
     *   player_name?: string|null,
     *   date: string,
     *   time: string,
     *   location_id: int,
     *   duration_minutes?: int,
     *   session_type?: string,
     *   amount?: float|null
     * }  $data
     */
    public function createCoachBooking(Coach $coach, array $data): Booking
    {
        $athleteId = ! empty($data['athlete_id']) ? (int) $data['athlete_id'] : null;
        $playerName = trim((string) ($data['player_name'] ?? ''));
        $date = $data['date'];
        $time = substr((string) $data['time'], 0, 5);
        $duration = max(30, (int) ($data['duration_minutes'] ?? 60));
        $locationId = (int) $data['location_id'];

        $athlete = null;
        if ($athleteId) {
            $athlete = User::query()
                ->whereKey($athleteId)
                ->where('role', User::ROLE_ATHLETE)
                ->first();

            if (! $athlete) {
                throw ValidationException::withMessages([
                    'athlete_id' => 'That player account was not found.',
                ]);
            }

            $playerName = $athlete->name;
        }

        if ($playerName === '') {
            throw ValidationException::withMessages([
                'player_name' => 'Choose a roster player or enter a client name.',
            ]);
        }

        $conflict = SessionRequest::schedulingConflictMessage(
            $athleteId ?? 0,
            $date,
            $time,
            $coach->id,
            null,
            $duration,
            'coach'
        );

        if ($conflict) {
            throw ValidationException::withMessages([
                'time' => $conflict,
            ]);
        }

        return Booking::query()->create([
            'reference' => Booking::generateReference(),
            'session_request_id' => null,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete?->id,
            'location_id' => $locationId,
            'athlete_name' => $playerName,
            'session_type' => $data['session_type'] ?? 'Private 1-on-1',
            'session_date' => $date,
            'session_time' => $time,
            'duration_minutes' => $duration,
            'amount' => array_key_exists('amount', $data) && $data['amount'] !== null
                ? (float) $data['amount']
                : (float) ($coach->rate ?? 0),
            'status' => 'confirmed',
        ]);
    }

    public function syncMissingForCoach(Coach $coach): int
    {
        $sessions = SessionRequest::query()
            ->where('host_coach_id', $coach->id)
            ->whereIn('status', ['hosted', 'awaiting_deposit', 'confirmed'])
            ->whereDoesntHave('bookings')
            ->with(['players', 'requester'])
            ->get();

        foreach ($sessions as $session) {
            $this->createFromAcceptedSession($session, $coach);
        }

        return $sessions->count();
    }
}
