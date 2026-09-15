<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coach;
use App\Models\SessionRequest;

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
