<?php

namespace App\Http\Controllers;

use App\Events\SessionRequestChanged;
use App\Models\Coach;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\SessionRequestPlayer;
use App\Services\AppMailer;
use App\Services\SessionBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SessionRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SessionRequest::query()
            ->with(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location'])
            ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
            ->orderByDesc('created_at');

        if ($user->isAthlete()) {
            $query->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                    ->orWhereHas('players', fn ($p) => $p->where('user_id', $user->id));
            });
        } elseif ($user->isAdmin()) {
            // View all requests, including private ones targeted at a specific coach.
        } elseif ($user->isCoach()) {
            $coach = $this->resolveCoachProfile($request);
            if (! $coach) {
                return response()->json(['data' => []]);
            }

            $query->visibleToCoach($coach);
        } else {
            $query->whereRaw('1 = 0');
        }

        $items = $query->limit(40)->get()->map->toPortalArray()->values();

        return response()->json(['data' => $items]);
    }

    public function show(Request $request, string $reference): JsonResponse
    {
        $session = SessionRequest::query()
            ->with(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location'])
            ->where('reference', $reference)
            ->firstOrFail();

        if (! $session->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'You cannot view this session request.'], 403);
        }

        return response()->json(['data' => $session->toPortalArray()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location_id' => ['nullable', 'string'],
            'location_name' => ['required', 'string', 'max:120'],
            'location_city' => ['nullable', 'string', 'max:120'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'session_time' => ['nullable', 'string', 'max:20'],
            'session_type' => ['required', 'string', 'max:160'],
            'sport' => ['nullable', 'string', 'max:80'],
            'age_range' => ['nullable', 'string', 'max:80'],
            'price_range' => ['nullable', 'string', 'max:80'],
            'player_level' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'min_players' => ['nullable', 'integer', 'min:1', 'max:30'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:30'],
            'know_by_at' => ['nullable', 'date'],
            'card_on_file' => ['nullable', 'string', 'max:120'],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'requested_coach_id' => ['nullable', 'integer', 'exists:coaches,id'],
        ]);

        $user = $request->user();
        $location = null;
        if (! empty($data['location_id'])) {
            $location = Location::query()
                ->where(function ($q) use ($data) {
                    $q->where('slug', $data['location_id']);
                    if (ctype_digit((string) $data['location_id'])) {
                        $q->orWhere('id', (int) $data['location_id']);
                    }
                })
                ->first();
        }

        $requestedCoach = null;
        if (! empty($data['requested_coach_id'])) {
            $requestedCoach = Coach::query()
                ->where('id', $data['requested_coach_id'])
                ->where('status', 'active')
                ->first();

            if (! $requestedCoach) {
                return response()->json(['message' => 'That coach is not available for booking.'], 422);
            }
        }

        $time = null;
        if (! empty($data['session_time'])) {
            try {
                $time = Carbon::parse($data['session_time'])->format('H:i:s');
            } catch (\Throwable) {
                $time = null;
            }
        }

        $conflict = SessionRequest::schedulingConflictMessage(
            $user->id,
            $data['session_date'],
            $time,
            $requestedCoach?->id
        );
        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $session = DB::transaction(function () use ($data, $user, $location, $time, $requestedCoach) {
            $session = SessionRequest::query()->create([
                'reference' => SessionRequest::generateReference(),
                'requester_id' => $user->id,
                'location_id' => $location?->id,
                'location_name' => $data['location_name'],
                'location_city' => $data['location_city'] ?? $location?->area,
                'session_date' => $data['session_date'],
                'session_time' => $time,
                'session_type' => $data['session_type'],
                'sport' => $data['sport'] ?? 'Soccer',
                'age_range' => $data['age_range'] ?? null,
                'price_range' => $data['price_range'] ?? null,
                'player_level' => $data['player_level'] ?? null,
                'notes' => $data['notes'] ?? null,
                'min_players' => $data['min_players'] ?? null,
                'max_players' => $data['max_players'] ?? null,
                'looking_for' => isset($data['max_players']) ? max(0, ((int) $data['max_players']) - 1) : null,
                'know_by_at' => $data['know_by_at'] ?? null,
                'deposit_amount' => $data['deposit'] ?? 10,
                'card_on_file' => $data['card_on_file'] ?? null,
                'requested_coach_id' => $requestedCoach?->id,
                'status' => 'open',
            ]);

            $initials = Str::of($user->name)->explode(' ')->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

            SessionRequestPlayer::query()->create([
                'session_request_id' => $session->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'initials' => $initials ?: 'PL',
                'role' => 'requester',
                'paid' => false,
                'card_on_file' => $data['card_on_file'] ?? null,
            ]);

            return $session->load(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location']);
        });

        $payload = $session->toPortalArray();
        $this->broadcastSessionRequest($payload, 'created');
        app(AppMailer::class)->sendSessionRequestConfirmation($session);

        return response()->json(['data' => $payload], 201);
    }

    public function accept(Request $request, string $reference): JsonResponse
    {
        $session = SessionRequest::query()
            ->with(['players', 'requester', 'requestedCoach'])
            ->where('reference', $reference)
            ->firstOrFail();

        if ($session->status !== 'open') {
            return response()->json(['message' => 'This request is no longer open.'], 422);
        }

        if ($session->isAcceptCutoffPassed()) {
            $session->update(['status' => 'expired']);
            $this->broadcastSessionRequest($session->fresh()->load(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location'])->toPortalArray(), 'expired');

            return response()->json(['message' => 'This request expired — the parent’s cutoff has passed.'], 422);
        }

        $user = $request->user();
        if ($user?->isAdmin()) {
            return response()->json(['message' => 'Admins can view session requests but cannot accept them.'], 403);
        }

        $coach = $this->resolveCoachProfile($request);
        if (! $coach) {
            return response()->json(['message' => 'Coach profile required to host a session.'], 422);
        }

        if ($coach->status !== 'active') {
            return response()->json(['message' => 'Only active coaches can accept session requests.'], 422);
        }

        if (! $session->isVisibleToCoach($coach)) {
            return response()->json(['message' => 'This request was sent to a different coach.'], 403);
        }

        $time = $session->session_time
            ? Carbon::parse($session->session_time)->format('H:i:s')
            : null;

        if ($session->session_date) {
            $conflict = SessionRequest::schedulingConflictMessage(
                (int) $session->requester_id,
                $session->session_date->toDateString(),
                $time,
                $coach->id,
                $session->id,
                SessionRequest::defaultDurationMinutes(),
                'coach'
            );
            if ($conflict) {
                return response()->json(['message' => $conflict], 422);
            }
        }

        DB::transaction(function () use ($session, $coach) {
            $session->update([
                'status' => 'hosted',
                'host_coach_id' => $coach->id,
                'accepted_at' => now(),
            ]);

            $requester = $session->players()->where('role', 'requester')->first();
            if ($requester && $requester->card_on_file) {
                $requester->update([
                    'paid' => true,
                    'paid_with' => $requester->card_on_file,
                ]);
            }

            if ($session->looking_for === null && $session->max_players !== null) {
                $joined = $session->players()->count();
                $session->update([
                    'looking_for' => max(0, $session->max_players - $joined),
                ]);
            }

            app(SessionBookingService::class)->createFromAcceptedSession(
                $session->fresh(['players', 'requester']),
                $coach
            );
        });

        $session->refresh()->load(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location']);
        $payload = $session->toPortalArray();
        $this->broadcastSessionRequest($payload, 'accepted');
        app(AppMailer::class)->sendSessionRequestAccepted($session);

        return response()->json(['data' => $payload]);
    }

    public function decline(Request $request, string $reference): JsonResponse
    {
        $session = SessionRequest::query()
            ->with(['players', 'requester', 'requestedCoach'])
            ->where('reference', $reference)
            ->firstOrFail();

        $user = $request->user();
        if ($user?->isAdmin()) {
            return response()->json(['message' => 'Admins can view session requests but cannot decline them.'], 403);
        }

        if ($session->status !== 'open') {
            return response()->json(['message' => 'This request is no longer open.'], 422);
        }

        $coach = $this->resolveCoachProfile($request);
        if (! $coach || ! $session->isVisibleToCoach($coach)) {
            return response()->json(['message' => 'This request was sent to a different coach.'], 403);
        }

        $targeted = $session->requested_coach_id !== null
            && (int) $session->requested_coach_id === (int) $coach->id;

        if ($targeted) {
            $session->update([
                'status' => 'cancelled',
                'coach_note' => 'Declined by '.$coach->display_name,
            ]);
            $session->refresh()->load(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location']);
            $payload = $session->toPortalArray();
            $this->broadcastSessionRequest($payload, 'cancelled');

            return response()->json([
                'data' => $payload,
                'message' => 'You declined this request. The player has been notified.',
            ]);
        }

        return response()->json([
            'data' => $session->toPortalArray(),
            'hidden' => true,
            'message' => 'Hidden from your inbox. Other coaches can still accept it.',
        ]);
    }

    public function join(Request $request, string $reference): JsonResponse
    {
        $data = $request->validate([
            'paid' => ['sometimes', 'boolean'],
            'paid_with' => ['nullable', 'string', 'max:120'],
            'card_on_file' => ['nullable', 'string', 'max:120'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $session = SessionRequest::query()
            ->with('players')
            ->where('reference', $reference)
            ->firstOrFail();

        if (! in_array($session->status, ['hosted', 'awaiting_deposit', 'confirmed'], true) || ! $session->host_coach_id) {
            return response()->json(['message' => 'A coach must accept this request before other players can join.'], 422);
        }

        if ($session->isJoinClosed()) {
            return response()->json(['message' => 'Joining closed 30 minutes before the session starts.'], 422);
        }

        if ($session->max_players !== null && $session->players->count() >= $session->max_players) {
            return response()->json(['message' => 'This session is full.'], 422);
        }

        $user = $request->user();
        if ($session->players->contains(fn ($p) => $p->user_id === $user->id)) {
            return response()->json(['message' => 'You already joined this request.'], 422);
        }

        $name = $data['name'] ?? $user->name;
        $initials = Str::of($name)->explode(' ')->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

        DB::transaction(function () use ($session, $user, $name, $initials, $data) {
            SessionRequestPlayer::query()->create([
                'session_request_id' => $session->id,
                'user_id' => $user->id,
                'name' => $name,
                'initials' => $initials ?: 'PL',
                'role' => 'joiner',
                'paid' => (bool) ($data['paid'] ?? false),
                'paid_with' => $data['paid_with'] ?? null,
                'card_on_file' => $data['card_on_file'] ?? null,
            ]);

            $joined = $session->players()->count();
            if ($session->max_players !== null) {
                $session->update(['looking_for' => max(0, $session->max_players - $joined)]);
            }
        });

        $session->refresh()->load(['players', 'requester', 'hostCoach.user', 'location']);
        $payload = $session->toPortalArray();
        $this->broadcastSessionRequest($payload, 'joined');

        return response()->json(['data' => $payload]);
    }

    public function cancel(Request $request, string $reference): JsonResponse
    {
        $session = SessionRequest::query()
            ->with(['players', 'requester', 'hostCoach.user', 'location'])
            ->where('reference', $reference)
            ->firstOrFail();

        $user = $request->user();

        if (! $session->canBeCancelledBy($user)) {
            return response()->json([
                'message' => $session->requester_id === $user?->id
                    ? 'Only open requests waiting for a coach can be cancelled.'
                    : 'You can only cancel session requests you created.',
            ], 403);
        }

        $session->update(['status' => 'cancelled']);
        $session->refresh()->load(['players', 'requester', 'hostCoach.user', 'location']);
        $payload = $session->toPortalArray();
        $this->broadcastSessionRequest($payload, 'cancelled');

        return response()->json([
            'data' => $payload,
            'player' => $session->toPlayerDashboardArray($user),
            'message' => 'Session request cancelled.',
        ]);
    }

    public function update(Request $request, string $reference): JsonResponse
    {
        $session = SessionRequest::query()
            ->with(['players', 'hostCoach'])
            ->where('reference', $reference)
            ->firstOrFail();

        $user = $request->user();
        $coach = $this->resolveCoachProfile($request);
        $isHost = $coach && $session->host_coach_id === $coach->id;

        if ($user->isAdmin() && ! $isHost) {
            return response()->json(['message' => 'Admins can view session requests but cannot accept or adjust them.'], 403);
        }

        if (! $isHost) {
            return response()->json(['message' => 'Only the hosting coach can adjust this request.'], 403);
        }

        $data = $request->validate([
            'min_players' => ['nullable', 'integer', 'min:1', 'max:30'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:30'],
            'looking_for' => ['nullable', 'integer', 'min:0', 'max:30'],
            'coach_note' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['hosted', 'awaiting_deposit', 'confirmed', 'expired', 'cancelled'])],
        ]);

        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $hostedStatuses = ['hosted', 'awaiting_deposit', 'confirmed'];
            if ($session->status === 'open' && in_array($data['status'], $hostedStatuses, true)) {
                return response()->json(['message' => 'Use Accept to host a session request.'], 403);
            }
        }

        $session->fill(collect($data)->only([
            'min_players',
            'max_players',
            'looking_for',
            'coach_note',
            'status',
        ])->all());
        $session->save();

        $session->load(['players', 'requester', 'hostCoach.user', 'location']);
        $payload = $session->toPortalArray();
        $this->broadcastSessionRequest($payload, 'updated');

        return response()->json(['data' => $payload]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function broadcastSessionRequest(array $payload, string $action): void
    {
        if (config('broadcasting.default') !== 'pusher') {
            return;
        }

        if (! config('broadcasting.connections.pusher.key')) {
            return;
        }

        try {
            SessionRequestChanged::dispatch($payload, $action);
        } catch (\Throwable) {
            // Never fail the HTTP request if broadcasting is misconfigured
        }
    }

    private function resolveCoachProfile(Request $request): ?Coach
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return Coach::query()->where('user_id', $user->id)->first();
    }
}
