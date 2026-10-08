<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\GroupJoinRequest;
use App\Models\User;
use App\Services\SessionBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CoachScheduleSessionController extends Controller
{
    public function store(Request $request, SessionBookingService $bookings): RedirectResponse|JsonResponse
    {
        $coach = $this->currentCoach();

        $data = $request->validate([
            'athlete_id' => ['nullable', 'integer', 'exists:users,id'],
            'player_name' => ['nullable', 'string', 'max:120'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'session_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'session_type' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'max_players' => ['nullable', 'integer', 'min:2', 'max:30'],
            'force_double_book' => ['nullable', 'boolean'],
            'week' => ['nullable', 'date'],
            'view' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);

        if (empty($data['athlete_id']) && blank($data['player_name'] ?? null)) {
            return $this->fail($request, ['player_name' => 'Choose a roster player or enter a client name.'], $data);
        }

        try {
            $booking = $bookings->createCoachBooking($coach, [
                'athlete_id' => $data['athlete_id'] ?? null,
                'player_name' => $data['player_name'] ?? null,
                'date' => $data['session_date'],
                'time' => $data['session_time'],
                'location_id' => $data['location_id'],
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'session_type' => $data['session_type'],
                'notes' => $data['notes'] ?? null,
                'max_players' => $data['max_players'] ?? null,
                'force_double_book' => (bool) ($data['force_double_book'] ?? false),
            ]);
        } catch (ValidationException $e) {
            return $this->fail($request, $e->errors(), $data);
        }

        $coach->forceFill(['last_active_at' => now()])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Session booked.',
                'booking_id' => $booking->id,
            ]);
        }

        return redirect()
            ->route('coach.schedule', array_filter([
                'view' => $data['view'] ?? $request->input('view', 'week'),
                'week' => $data['week'] ?? optional($booking->session_date)->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'date' => optional($booking->session_date)->toDateString(),
            ]))
            ->with('success', $booking->displayName().' was booked for '.$booking->whenLabel().'.');
    }

    public function update(Request $request, Booking $booking, SessionBookingService $bookings): RedirectResponse|JsonResponse
    {
        $coach = $this->currentCoach();
        $this->assertOwnsBooking($coach, $booking);

        $data = $request->validate([
            'athlete_id' => ['nullable', 'integer', 'exists:users,id'],
            'player_name' => ['nullable', 'string', 'max:120'],
            'session_date' => ['nullable', 'date'],
            'session_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'session_type' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'max_players' => ['nullable', 'integer', 'min:2', 'max:30'],
            'force_double_book' => ['nullable', 'boolean'],
            'add_players' => ['nullable', 'array'],
            'add_players.*.athlete_id' => ['nullable', 'integer', 'exists:users,id'],
            'add_players.*.player_name' => ['nullable', 'string', 'max:120'],
            'remove_booking_ids' => ['nullable', 'array'],
            'remove_booking_ids.*' => ['integer'],
            'view' => ['nullable', 'string'],
            'week' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
        ]);

        try {
            foreach ($data['remove_booking_ids'] ?? [] as $removeId) {
                $remove = Booking::query()->forCoach($coach->id)->whereKey($removeId)->first();
                if ($remove && $remove->group_session_id) {
                    $bookings->removePlayerFromGroup($coach, $remove);
                }
            }

            $updated = $bookings->updateCoachBooking($coach, $booking->fresh(), $data);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first(),
                    'errors' => $e->errors(),
                    'double_book' => isset($e->errors()['double_book']),
                ], 422);
            }

            return redirect()
                ->route('coach.schedule', array_filter([
                    'view' => $data['view'] ?? 'week',
                    'week' => $data['week'] ?? null,
                    'date' => $data['date'] ?? $data['session_date'] ?? null,
                    'manage' => $booking->id,
                ]))
                ->withErrors($e->errors())
                ->withInput();
        }

        $coach->forceFill(['last_active_at' => now()])->save();

        if ($request->expectsJson()) {
            $updated->load(['groupSession', 'location']);
            $group = $updated->groupSession;

            return response()->json([
                'message' => 'Session updated.',
                'booking_id' => $updated->id,
                'data' => [
                    'id' => $updated->id,
                    'group_session_id' => $updated->group_session_id,
                    'player_name' => $updated->displayName(),
                    'session_date' => $updated->session_date?->toDateString(),
                    'session_time' => substr((string) $updated->session_time, 0, 5),
                    'duration_minutes' => (int) ($updated->duration_minutes ?: 60),
                    'session_type' => $updated->session_type,
                    'notes' => $updated->notes,
                    'max_players' => $group?->max_players ?? $updated->max_players,
                    'players' => $group?->bookedCount(),
                    'capacity_label' => $group?->capacityLabel(),
                    'location' => $updated->location?->name,
                    'is_group' => (bool) $updated->group_session_id,
                ],
            ]);
        }

        return redirect()
            ->route('coach.schedule', array_filter([
                'view' => $data['view'] ?? 'week',
                'week' => $data['week'] ?? null,
                'date' => optional($updated->session_date)->toDateString(),
            ]))
            ->with('success', 'Session updated.');
    }

    public function move(Request $request, Booking $booking, SessionBookingService $bookings): JsonResponse
    {
        $coach = $this->currentCoach();
        $this->assertOwnsBooking($coach, $booking);

        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'session_time' => ['required', 'date_format:H:i'],
            'force_double_book' => ['nullable', 'boolean'],
        ]);

        try {
            $updated = $bookings->moveCoachBooking(
                $coach,
                $booking,
                $data['session_date'],
                $data['session_time'],
                (bool) ($data['force_double_book'] ?? false)
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
                'double_book' => isset($e->errors()['double_book']),
            ], 422);
        }

        $coach->forceFill(['last_active_at' => now()])->save();

        return response()->json([
            'message' => 'Session moved.',
            'booking_id' => $updated->id,
            'date' => optional($updated->session_date)->toDateString(),
            'time' => substr((string) $updated->session_time, 0, 5),
        ]);
    }

    public function destroy(Request $request, Booking $booking, SessionBookingService $bookings): RedirectResponse|JsonResponse
    {
        $coach = $this->currentCoach();
        $this->assertOwnsBooking($coach, $booking);
        $date = $booking->session_date?->toDateString();

        $bookings->cancelCoachBooking($coach, $booking);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Session cancelled.']);
        }

        return redirect()
            ->route('coach.schedule', array_filter([
                'view' => $request->input('view', 'week'),
                'week' => $request->input('week'),
                'date' => $request->input('date', $date),
            ]))
            ->with('success', 'Session cancelled.');
    }

    public function show(Booking $booking): JsonResponse
    {
        $coach = $this->currentCoach();
        $this->assertOwnsBooking($coach, $booking);

        $booking->load([
            'athlete',
            'location',
            'groupSession.bookings' => fn ($q) => $q->where('status', '!=', 'cancelled'),
        ]);

        $group = $booking->groupSession;
        $players = $group
            ? $group->bookings->map(fn (Booking $b) => [
                'booking_id' => $b->id,
                'athlete_id' => $b->athlete_id,
                'name' => $b->displayName(),
            ])->values()->all()
            : [[
                'booking_id' => $booking->id,
                'athlete_id' => $booking->athlete_id,
                'name' => $booking->displayName(),
            ]];

        return response()->json([
            'data' => [
                'id' => $booking->id,
                'group_session_id' => $booking->group_session_id,
                'athlete_id' => $booking->athlete_id,
                'player_name' => $booking->displayName(),
                'session_date' => $booking->session_date?->toDateString(),
                'session_time' => substr((string) $booking->session_time, 0, 5),
                'duration_minutes' => (int) ($booking->duration_minutes ?: 60),
                'location_id' => $booking->location_id,
                'session_type' => $booking->session_type,
                'notes' => $booking->notes,
                'max_players' => $group?->max_players ?? $booking->max_players,
                'is_group' => (bool) $booking->group_session_id,
                'players' => $players,
                'capacity_label' => $group?->capacityLabel(),
            ],
        ]);
    }

    public function acceptJoin(Request $request, GroupJoinRequest $join, SessionBookingService $bookings): JsonResponse|RedirectResponse
    {
        $coach = $this->currentCoach();
        $join->load('groupSession');

        try {
            $booking = $bookings->acceptGroupJoinRequest($coach, $join);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }

            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Player added to group.', 'booking_id' => $booking->id]);
        }

        return back()->with('success', $booking->displayName().' joined the group session.');
    }

    public function declineJoin(Request $request, GroupJoinRequest $join, SessionBookingService $bookings): JsonResponse|RedirectResponse
    {
        $coach = $this->currentCoach();
        $join->load('groupSession');
        $bookings->declineGroupJoinRequest($coach, $join);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Join request declined.']);
        }

        return back()->with('success', 'Join request declined.');
    }

    private function assertOwnsBooking(Coach $coach, Booking $booking): void
    {
        abort_unless((int) $booking->coach_id === (int) $coach->id, 404);
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $data
     */
    private function fail(Request $request, array $errors, array $data): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => collect($errors)->flatten()->first(),
                'errors' => $errors,
                'double_book' => isset($errors['double_book']),
            ], 422);
        }

        return redirect()
            ->route('coach.schedule', array_filter([
                'view' => $data['view'] ?? $request->input('view', 'week'),
                'week' => $data['week'] ?? $request->query('week'),
                'date' => $data['session_date'] ?? $request->query('date'),
                'book' => 1,
            ]))
            ->withErrors($errors)
            ->withInput();
    }

    private function currentCoach(): Coach
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->isCoach(), 403);

        $coach = Coach::query()->where('user_id', $user->id)->first();
        abort_unless($coach, 403);

        return $coach;
    }
}
