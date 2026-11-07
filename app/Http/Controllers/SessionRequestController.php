<?php

namespace App\Http\Controllers;

use App\Events\SessionRequestChanged;
use App\Models\Coach;
use App\Models\CoachGroupSession;
use App\Models\Location;
use App\Models\SessionRequest;
use App\Models\SessionRequestPlayer;
use App\Models\User;
use App\Services\AppMailer;
use App\Services\SessionBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SessionRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $coach = null;
        SessionRequest::expireStale();

        $query = SessionRequest::query()
            ->with(['players.user', 'requester', 'hostCoach.user', 'requestedCoach.user', 'invitedCoaches', 'location'])
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

            $query->visibleToCoach($coach)->current();
        } else {
            $query->whereRaw('1 = 0');
        }

        $items = $query->limit(40)->get()
            ->map(fn (SessionRequest $session) => $this->dataFor($session, $user, $coach))
            ->values();

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

        return response()->json(['data' => $this->dataFor($session, $request->user(), $this->resolveCoachProfile($request))]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->hasMemberAccess()) {
            return response()->json(['message' => 'Session requests are part of the Family Membership.', 'membership_required' => true], 403);
        }

        $data = $request->validate([
            'location_id' => ['nullable', 'string'],
            'location_name' => ['required', 'string', 'max:120'],
            'location_city' => ['nullable', 'string', 'max:120'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'session_time' => ['nullable', 'string', 'max:20'],
            'session_type' => ['required', 'string', 'max:160'],
            'sport' => ['required', 'string', Rule::in(User::SPORTS)],
            'age_range' => ['nullable', 'string', 'max:255'], // e.g. "U10, U11" — optional, multi-select
            'price_range' => ['nullable', 'string', 'max:80'], // e.g. "Up to $30 / player"
            'player_level' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'min_players' => ['nullable', 'integer', 'min:1', 'max:30'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:30'],
            'know_by_at' => ['nullable', 'date'],
            'phone' => [Rule::requiredIf(blank($request->user()->phone)), 'nullable', 'string', 'max:40'],
            'requested_coach_id' => ['nullable', 'integer', 'exists:coaches,id'],
            'requested_coach_ids' => ['nullable', 'array', 'max:8'],
            'requested_coach_ids.*' => ['integer', 'exists:coaches,id'],
        ], [
            'phone.required' => 'Add a phone number so your coach can reach you.',
        ]);

        $user = $request->user();
        $this->savePhone($user, $data['phone'] ?? null);
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

        $requestedCoachIds = collect($data['requested_coach_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($requestedCoachIds->isEmpty() && ! empty($data['requested_coach_id'])) {
            $requestedCoachIds = collect([(int) $data['requested_coach_id']]);
        }

        $invitedCoaches = collect();
        if ($requestedCoachIds->isNotEmpty()) {
            $invitedCoaches = Coach::query()
                ->whereIn('id', $requestedCoachIds->all())
                ->where('status', 'active')
                ->get();

            if ($invitedCoaches->count() !== $requestedCoachIds->count()) {
                return response()->json(['message' => 'One or more selected coaches are not available.'], 422);
            }
        }

        $requestedCoach = $invitedCoaches->first();

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

        $session = DB::transaction(function () use ($data, $user, $location, $time, $requestedCoach, $invitedCoaches) {
            $session = SessionRequest::query()->create([
                'reference' => SessionRequest::generateReference(),
                'requester_id' => $user->id,
                'location_id' => $location?->id,
                'location_name' => $data['location_name'],
                'location_city' => $data['location_city'] ?? $location?->area,
                'session_date' => $data['session_date'],
                'session_time' => $time,
                'session_type' => $data['session_type'],
                'sport' => $data['sport'],
                'age_range' => $data['age_range'] ?? null,
                'price_range' => $data['price_range'] ?? null,
                'player_level' => $data['player_level'] ?? null,
                'notes' => $data['notes'] ?? null,
                'min_players' => $data['min_players'] ?? null,
                'max_players' => $data['max_players'] ?? null,
                'looking_for' => isset($data['max_players']) ? max(0, ((int) $data['max_players']) - 1) : null,
                // Browsers send this in UTC; saved as-is it would read as Pacific, hours late.
                'know_by_at' => ! empty($data['know_by_at'])
                    ? Carbon::parse($data['know_by_at'])->setTimezone(config('app.timezone'))
                    : null,
                'deposit_amount' => 0,
                'requested_coach_id' => $requestedCoach?->id,
                'status' => 'open',
            ]);

            if ($invitedCoaches->isNotEmpty()) {
                $session->invitedCoaches()->sync($invitedCoaches->pluck('id')->all());
            }

            $initials = Str::of($user->name)->explode(' ')->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

            SessionRequestPlayer::query()->create([
                'session_request_id' => $session->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'initials' => $initials ?: 'PL',
                'role' => 'requester',
                'status' => SessionRequestPlayer::PENDING,
            ]);

            return $session->load(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'invitedCoaches', 'location']);
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
            return response()->json(['message' => 'This request is no longer available. Another coach may have accepted it.'], 422);
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

        // Open marketplace (not targeted to specific coaches): free for everyone during the
        // launch window, then subscribed coaches only. Direct requests/bookings are always allowed.
        if (! $session->isTargeted() && ! $coach->hasMarketplaceAccess()) {
            return response()->json([
                'message' => 'Open session requests require a CoachNow subscription with marketplace access. You can still accept requests sent directly to you and take direct bookings.',
            ], 403);
        }

        if (! $session->isVisibleToCoach($coach)) {
            return response()->json(['message' => 'This request was sent to a different coach.'], 403);
        }

        $data = $request->validate([
            'price_per_player' => ['required', 'numeric', 'min:0', 'max:1000'],
            'payment_note' => ['nullable', 'string', 'max:500'],
        ], [
            'price_per_player.required' => 'Enter your price per player.',
        ]);

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

        // Locked so that when two coaches accept together, only the first gets it.
        $won = DB::transaction(function () use ($session, $coach, $data) {
            $locked = SessionRequest::query()->lockForUpdate()->find($session->id);
            if (! $locked || $locked->status !== 'open') {
                return false;
            }

            $note = trim((string) ($data['payment_note'] ?? '')) ?: null;
            $locked->update([
                'status' => 'awaiting_deposit',
                'host_coach_id' => $coach->id,
                'accepted_at' => now(),
                'price_per_player' => $data['price_per_player'],
                'payment_note' => $note,
            ]);

            $coach->forceFill([
                'last_active_at' => now(),
                'payment_instructions' => $note ?? $coach->payment_instructions,
            ])->save();
            if ($coach->shouldFlagForApprovalReview()) {
                $coach->forceFill(['approval_status' => 'flagged'])->save();
            }

            $accepted = $locked->fresh(['players', 'requester']);
            $accepted->update(['looking_for' => $accepted->spotsLeft()]);
            app(SessionBookingService::class)->createFromAcceptedSession($accepted, $coach);

            return true;
        });

        if (! $won) {
            return response()->json(['message' => 'Another coach just accepted this request.'], 422);
        }

        $session->refresh()->load(['players.user', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location']);
        $this->broadcastSessionRequest($session->toPortalArray(), 'accepted');
        app(AppMailer::class)->sendSessionRequestAccepted($session);

        return response()->json(['data' => $session->toCoachPortalArray($coach)]);
    }

    /**
     * The coach received this family's deposit, so they confirm the place.
     */
    public function confirmPlayer(Request $request, string $reference, int $player, SessionBookingService $bookings): JsonResponse
    {
        [$session, $coach] = $this->hostedSession($request, $reference);

        try {
            $confirmed = $bookings->confirmRequestPlayer($session, $player, $coach);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return $this->afterPlaceChange($session, $coach, $confirmed, true, 'confirmed');
    }

    public function declinePlayer(Request $request, string $reference, int $player, SessionBookingService $bookings): JsonResponse
    {
        [$session, $coach] = $this->hostedSession($request, $reference);

        try {
            $declined = $bookings->declineRequestPlayer($session, $player);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return $this->afterPlaceChange($session, $coach, $declined, false, 'declined');
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
            return response()->json(['message' => 'This request is no longer available.'], 422);
        }

        $coach = $this->resolveCoachProfile($request);
        if (! $coach || ! $session->isVisibleToCoach($coach)) {
            return response()->json(['message' => 'This request was sent to a different coach.'], 403);
        }

        // Other selected coaches can still take it, so only this coach steps out.
        $invited = $session->invitedCoachIds();
        if (count($invited) > 1 && in_array((int) $coach->id, $invited, true)) {
            $remaining = array_values(array_diff($invited, [(int) $coach->id]));
            $session->invitedCoaches()->detach($coach->id);
            if ((int) $session->requested_coach_id === (int) $coach->id) {
                $session->update(['requested_coach_id' => $remaining[0]]);
            }

            $session->refresh()->load(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'invitedCoaches', 'location']);
            $payload = $session->toPortalArray();
            $this->broadcastSessionRequest($payload, 'updated');

            return response()->json([
                'data' => $payload,
                'hidden' => true,
                'message' => 'Removed from your inbox. The other selected coaches can still accept it.',
            ]);
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

    /**
     * Ask to join. The coach arranges payment with the family, then confirms their place.
     */
    public function join(Request $request, string $reference): JsonResponse
    {
        if (! $request->user()?->hasMemberAccess()) {
            return response()->json(['message' => 'Joining session requests is part of the Family Membership.', 'membership_required' => true], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => [Rule::requiredIf(blank($request->user()->phone)), 'nullable', 'string', 'max:40'],
        ], [
            'phone.required' => 'Add a phone number so the coach can reach you.',
        ]);

        $session = SessionRequest::query()
            ->with(['players', 'hostCoach'])
            ->where('reference', $reference)
            ->firstOrFail();

        // Families can ask to join before or after a coach accepts. Requests sent to specific
        // coaches stay private.
        $waitingForCoach = $session->status === 'open';
        if ($session->isUnhostedAndExpired()) {
            return response()->json(['message' => 'No coach accepted this request before its deadline, so it is closed.'], 422);
        }
        if (! $waitingForCoach && (! in_array($session->status, ['hosted', 'awaiting_deposit', 'confirmed'], true) || ! $session->host_coach_id)) {
            return response()->json(['message' => 'This request is no longer open to join.'], 422);
        }

        if ($session->isTargeted()) {
            return response()->json(['message' => 'This request was sent to a specific coach and is private.'], 403);
        }

        if ($session->isJoinClosed()) {
            return response()->json(['message' => 'Joining closed 1 hour before the session starts.'], 422);
        }

        $user = $request->user();
        $this->savePhone($user, $data['phone'] ?? null);
        $name = $data['name'] ?? $user->name;
        $initials = Str::of($name)->explode(' ')->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

        // Locked so the last place can't be asked for twice at once.
        $problem = DB::transaction(function () use ($session, $user, $name, $initials) {
            $locked = SessionRequest::query()->with('players')->lockForUpdate()->find($session->id);

            if ($locked->playerFor($user)) {
                return 'You already asked to join this session.';
            }

            if ($locked->isFull()) {
                return 'This session is full.';
            }

            SessionRequestPlayer::query()->create([
                'session_request_id' => $locked->id,
                'user_id' => $user->id,
                'name' => $name,
                'initials' => $initials ?: 'PL',
                'role' => 'joiner',
                'status' => SessionRequestPlayer::PENDING,
            ]);

            return null;
        });

        if ($problem) {
            return response()->json(['message' => $problem], 422);
        }

        $session->refresh()->load(['players.user', 'requester', 'hostCoach.user', 'location']);
        $this->broadcastSessionRequest($session->toPortalArray(), 'joined');
        if (! $waitingForCoach) {
            app(AppMailer::class)->sendJoinRequestToCoach($session, $session->playerFor($user));
        }

        return response()->json([
            'data' => $session->toFamilyPortalArray($user),
            'message' => $waitingForCoach
                ? 'Your request to join is in. Once a coach accepts, they’ll confirm your place.'
                : 'Your request to join is in. Message the coach to arrange payment.',
        ]);
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

        if (($data['max_players'] ?? null) !== null && $data['max_players'] < $session->placesTaken()) {
            return response()->json([
                'message' => 'Max players can’t be lower than the '.$session->placesTaken().' already on this session.',
            ], 422);
        }

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

        // The calendar group has to match, or confirming would disagree with what's shown here.
        if (($data['max_players'] ?? null) !== null) {
            $groupId = $session->bookings()->whereNotNull('group_session_id')->value('group_session_id');
            CoachGroupSession::query()->whereKey($groupId)->first()?->update(['max_players' => max(2, $data['max_players'])]);
        }

        $session->load(['players.user', 'requester', 'hostCoach.user', 'location']);
        $this->broadcastSessionRequest($session->toPortalArray(), 'updated');

        return response()->json(['data' => $session->toCoachPortalArray($coach)]);
    }

    /**
     * @return array{0: SessionRequest, 1: Coach}
     */
    private function hostedSession(Request $request, string $reference): array
    {
        $session = SessionRequest::query()->where('reference', $reference)->firstOrFail();
        $coach = $this->resolveCoachProfile($request);

        abort_unless($coach && (int) $session->host_coach_id === (int) $coach->id, 403, 'Only the hosting coach can do this.');

        return [$session, $coach];
    }

    private function afterPlaceChange(SessionRequest $session, Coach $coach, SessionRequestPlayer $player, bool $confirmed, string $action): JsonResponse
    {
        $session->refresh()->load(['players.user', 'requester', 'hostCoach.user', 'location']);
        $this->broadcastSessionRequest($session->toPortalArray(), $action);
        app(AppMailer::class)->sendPlaceUpdate($session, $player->fresh(), $confirmed);

        return response()->json(['data' => $session->toCoachPortalArray($coach)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataFor(SessionRequest $session, User $user, ?Coach $coach): array
    {
        if ($user->isAdmin()) {
            return $session->toPortalArray();
        }

        return $user->isCoach() ? $session->toCoachPortalArray($coach) : $session->toFamilyPortalArray($user);
    }

    private function savePhone(User $user, ?string $phone): void
    {
        if (filled($phone) && blank($user->phone)) {
            $user->forceFill(['phone' => trim($phone)])->save();
        }
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
