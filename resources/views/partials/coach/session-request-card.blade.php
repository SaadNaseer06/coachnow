@php
  $status = $request['status'] ?? 'open';
  $isOpen = $status === 'open';
  $isHosted = in_array($status, ['accepted', 'confirmed', 'awaiting_deposit', 'hosted'], true);
  $depositPaid = ! empty($request['deposit_paid']) || $status === 'confirmed';
  $joined = (int) ($request['players_joined'] ?? ($isHosted ? 1 : 0));
  $minPlayers = $request['min_players'] ?? '';
  $maxPlayers = $request['max_players'] ?? '';
  $lookingFor = $request['looking_for'] ?? '';
  if ($lookingFor === '' && $maxPlayers !== '' && $maxPlayers !== null) {
    $lookingFor = max(0, (int) $maxPlayers - $joined);
  }
  $playersLabel = match (true) {
    $minPlayers !== '' && $maxPlayers !== '' => $minPlayers.'–'.$maxPlayers,
    $minPlayers !== '' => $minPlayers.'+',
    $maxPlayers !== '' => 'Up to '.$maxPlayers,
    default => '',
  };
@endphp

<article
  class="coach-req-card {{ $isOpen ? 'is-open' : '' }} {{ $isHosted ? 'is-hosted' : '' }} {{ $depositPaid ? 'is-confirmed' : '' }}"
  data-request-id="{{ $request['id'] ?? '' }}"
  data-min-players="{{ $minPlayers }}"
  data-max-players="{{ $maxPlayers }}"
  data-players-joined="{{ $joined }}"
  data-looking-for="{{ $lookingFor }}"
  data-coach-note="{{ $request['coach_note'] ?? '' }}"
  data-session-type="{{ $request['session_type'] ?? '' }}"
  @if (! empty($request['accept_seconds']) && $isOpen)
    data-accept-seconds="{{ $request['accept_seconds'] }}"
  @endif
>
  <div class="coach-req-card__top">
    <div class="coach-req-card__player">
      <div class="admin-person-fallback">{{ $request['initials'] ?? '??' }}</div>
      <div>
        <strong>{{ $request['name'] ?? 'Player' }}</strong>
        <span>{{ $request['posted'] ?? 'Just now' }} · {{ $request['id'] ?? '' }}</span>
      </div>
    </div>
    @if ($isOpen)
      <span class="coach-req-status coach-req-status--open"><span class="coach-req-pulse"></span> Needs host</span>
    @elseif ($status === 'awaiting_deposit')
      <span class="coach-req-status coach-req-status--accepted">Deposit pending</span>
    @elseif ($isHosted)
      <span class="coach-req-status coach-req-status--hosted"><span class="coach-req-pulse coach-req-pulse--green"></span> Open to join</span>
    @else
      <span class="coach-req-status">Closed</span>
    @endif
  </div>

  <div class="coach-req-card__grid">
    <div>
      <dt>When</dt>
      <dd>{{ $request['when'] ?? '—' }}</dd>
    </div>
    <div>
      <dt>Location</dt>
      <dd>{{ $request['location'] ?? '—' }}<span>{{ $request['city'] ?? '' }}</span></dd>
    </div>
    <div>
      <dt>Session type</dt>
      <dd data-field="session_type">{{ $request['session_type'] ?? '—' }}</dd>
    </div>
    <div>
      <dt>Age range</dt>
      <dd>{{ $request['age_range'] ?? '—' }}</dd>
    </div>
    <div>
      <dt>Price / player</dt>
      <dd>{{ $request['price_range'] ?? '—' }}</dd>
    </div>
    <div>
      <dt>Sport</dt>
      <dd>{{ $request['sport'] ?? 'Soccer' }}</dd>
    </div>
    <div data-players-row @if ($playersLabel === '' && ! $isHosted) hidden @endif>
      <dt>Players</dt>
      <dd data-field="players">{{ $playersLabel !== '' ? $playersLabel : '—' }}</dd>
    </div>
    @if (! empty($request['player_level']))
      <div>
        <dt>Level</dt>
        <dd>{{ $request['player_level'] }}</dd>
      </div>
    @endif
    @if (! empty($request['know_by']) && $isOpen)
      <div>
        <dt>Need to know by</dt>
        <dd>{{ $request['know_by'] }}</dd>
      </div>
    @endif
  </div>

  @if (! empty($request['notes']))
    <p class="coach-req-card__notes">{{ $request['notes'] }}</p>
  @endif

  @if ($isOpen)
    <div class="coach-req-card__timer">
      <span class="coach-req-card__timer-label">Accept by</span>
      <span class="coach-req-countdown" data-countdown>—</span>
    </div>
    <div class="coach-req-card__actions">
      <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-accept-request>Accept &amp; host</button>
      <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-request>Decline</button>
    </div>
    <p class="coach-req-card__hint">If you accept, the parent confirms with a $10 deposit. The request stays open so other players can join until 30 minutes before start.</p>
  @elseif ($isHosted)
    <div class="coach-req-card__hosted">
      <div class="coach-req-card__hosted-meta">
        <span data-field="joined">{{ $joined }} player{{ $joined === 1 ? '' : 's' }} joined</span>
        <span data-field="spots">
          @if ((int) $lookingFor > 0)
            Looking for {{ $lookingFor }} more
          @elseif ($maxPlayers !== '' && $joined >= (int) $maxPlayers)
            Full
          @else
            Open for more players
          @endif
        </span>
        <span>{{ $depositPaid ? '$10 deposit confirmed' : 'Awaiting $10 deposit' }}</span>
      </div>
      @if (! empty($request['coach_note']))
        <p class="coach-req-card__coach-note">{{ $request['coach_note'] }}</p>
      @endif
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-adjust-details>View &amp; adjust details</button>
      </div>
      <p class="coach-req-card__hint">You’re the host. This session stays visible so others can join until 30 minutes before start.</p>
    </div>
  @endif
</article>
