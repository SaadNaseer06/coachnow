@php
  $status = $request['status'] ?? 'open';
  $isOpen = $status === 'open';
  $isHosted = in_array($status, ['accepted', 'confirmed', 'awaiting_deposit', 'hosted'], true);
  $players = $request['players'] ?? [];
  $joined = (int) ($request['players_joined'] ?? count($players));
  $confirmedCount = (int) ($request['confirmed_count'] ?? 0);
  $pendingCount = (int) ($request['pending_count'] ?? 0);
  $minPlayers = $request['min_players'] ?? '';
  $maxPlayers = $request['max_players'] ?? '';
  $lookingFor = $request['looking_for'] ?? '';
  $playersLabel = match (true) {
      $minPlayers !== '' && $maxPlayers !== '' => $minPlayers.'–'.$maxPlayers,
      $minPlayers !== '' => $minPlayers.'+',
      $maxPlayers !== '' => 'Up to '.$maxPlayers,
      default => '',
  };

  $priceRaw = (string) ($request['price_range'] ?? '');
  $estCount = $joined > 0 ? $joined : (int) ($maxPlayers !== '' ? $maxPlayers : ($minPlayers !== '' ? $minPlayers : 0));
  $preferredCoach = trim((string) ($request['requested_coach'] ?? ''));
  $isTargeted = $preferredCoach !== '' || ($request['requested_coach_id'] ?? null) !== null;
  $requestedForYou = $isTargeted
      && isset($currentCoachId)
      && $currentCoachId !== null
      && (int) ($request['requested_coach_id'] ?? 0) === (int) $currentCoachId;
  $viewerCanAccept = $isOpen
      && empty($sessionRequestsIsAdmin ?? false)
      && isset($currentCoachId)
      && $currentCoachId !== null
      && (
          empty($request['requested_coach_id'])
          || (int) $request['requested_coach_id'] === (int) $currentCoachId
      )
      && (
          empty($request['acceptExpiresAt'])
          || (int) $request['acceptExpiresAt'] > (int) (microtime(true) * 1000)
      );
  $estPayout = '';
  $estLabel = $joined > 0 ? 'Est. payout' : 'Est. if filled';
  if ($estCount > 0 && $priceRaw !== '') {
      if (preg_match('/up to\s*\$?\s*(\d+)/i', $priceRaw, $m)) {
          $estPayout = 'Up to $'.number_format(((int) $m[1]) * $estCount).($joined > 0 ? ' est.' : '').' ('.$estCount.' × up to $'.$m[1].')';
      } elseif (preg_match('/\$?\s*(\d+)\s*[–\-]\s*\$?\s*(\d+)/', $priceRaw, $m)) {
          $low = ((int) $m[1]) * $estCount;
          $high = ((int) $m[2]) * $estCount;
          $estPayout = '$'.number_format($low).' – $'.number_format($high).($joined > 0 ? ' est.' : '').' ('.$estCount.' × '.$m[1].'–'.$m[2].')';
      } elseif (preg_match('/\$?\s*(\d+)\s*\+/', $priceRaw, $m)) {
          $estPayout = '$'.number_format(((int) $m[1]) * $estCount).'+'.($joined > 0 ? ' est.' : '').' ('.$estCount.' × $'.$m[1].'+)';
      }
  }
@endphp

<article
  class="coach-req-card {{ $isOpen ? 'is-open' : '' }} {{ $isHosted ? 'is-hosted' : '' }}"
  data-request-id="{{ $request['id'] ?? '' }}"
  data-min-players="{{ $minPlayers }}"
  data-max-players="{{ $maxPlayers }}"
  data-players-joined="{{ $joined }}"
  data-looking-for="{{ $lookingFor }}"
  data-coach-note="{{ $request['coach_note'] ?? '' }}"
  data-session-type="{{ $request['session_type'] ?? '' }}"
  data-price-range="{{ $request['price_range'] ?? '' }}"
  data-requested-coach-id="{{ $request['requested_coach_id'] ?? '' }}"
  data-players='@json($players)'
  @if (! empty($request['accept_seconds']) && $isOpen)
    data-accept-seconds="{{ $request['accept_seconds'] }}"
  @endif
  @if (! empty($request['acceptExpiresAt']) && $isOpen)
    data-accept-expires="{{ $request['acceptExpiresAt'] }}"
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
    @elseif ($isHosted)
      <span class="coach-req-status coach-req-status--hosted"><span class="coach-req-pulse coach-req-pulse--green"></span> {{ $status === 'awaiting_deposit' ? 'Awaiting deposit' : ($lookingFor === 0 ? 'Full' : 'Open to join') }}</span>
    @else
      <span class="coach-req-status">Closed</span>
    @endif
  </div>

  <p class="coach-req-card__target {{ $isTargeted ? '' : 'coach-req-card__target--open' }}">
    @if ($isTargeted)
      @if ($requestedForYou)
        Requested for you
        @if ($preferredCoach !== '')
          · {{ $preferredCoach }}
        @endif
      @else
        Preferred coach · {{ $preferredCoach !== '' ? $preferredCoach : 'Selected coach' }}
      @endif
    @else
      Open to any coach
    @endif
  </p>

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
      <dt>Preferred coach</dt>
      <dd>{{ $isTargeted ? ($preferredCoach !== '' ? $preferredCoach : 'Selected coach') : 'Any coach' }}</dd>
    </div>
    <div>
      <dt>Age range</dt>
      <dd>{{ $request['age_range'] ?? '—' }}</dd>
    </div>
    <div>
      <dt>Budget / player</dt>
      <dd>{{ $request['price_range'] ?? '—' }}</dd>
    </div>
    @if ($estPayout !== '')
      <div>
        <dt>{{ $estLabel }}</dt>
        <dd>{{ $estPayout }}</dd>
      </div>
    @endif
    <div>
      <dt>Sport</dt>
      <dd>{{ $request['sport'] ?? '—' }}</dd>
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
    @if ($viewerCanAccept)
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-accept-request>Accept &amp; host</button>
        <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-request>Decline</button>
      </div>
      <p class="coach-req-card__hint">Accept to set your price. You and the family arrange payment directly; CoachNow doesn’t collect it.</p>
    @else
      <p class="coach-req-card__hint">View only — this request is waiting for {{ $isTargeted && $preferredCoach !== '' ? $preferredCoach : 'a coach' }} to accept.</p>
    @endif
  @elseif ($isHosted)
    <div class="coach-req-card__hosted">
      <div class="coach-req-card__hosted-meta">
        @if (! empty($request['price_label']))
          <span data-field="price">{{ $request['price_label'] }}</span>
        @endif
        <span data-field="confirmed">{{ $confirmedCount }} confirmed</span>
        <span data-field="pending">{{ $pendingCount }} pending</span>
        <span data-field="spots">
          @if ($lookingFor === '')
            Open for more players
          @elseif ((int) $lookingFor > 0)
            Looking for {{ $lookingFor }} more
          @else
            Full
          @endif
        </span>
      </div>

      <div class="coach-req-roster" data-roster>
        @foreach ($players as $player)
          <div class="coach-req-roster__row">
            <div class="coach-req-roster__who">
              <div class="admin-person-fallback">{{ $player['initials'] ?? 'PL' }}</div>
              <div>
                <strong>{{ $player['name'] ?? 'Player' }}</strong>
                <span>{{ ($player['role'] ?? '') === 'requester' ? 'Original requester' : 'Asked to join' }}</span>
                @if (! empty($player['email']) || ! empty($player['phone']))
                  <span class="coach-req-contact">
                    @if (! empty($player['email']))
                      <a href="mailto:{{ $player['email'] }}">{{ $player['email'] }}</a>
                    @endif
                    @if (! empty($player['phone']))
                      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $player['phone']) }}">{{ $player['phone'] }}</a>
                    @endif
                  </span>
                @endif
              </div>
            </div>
            <div class="coach-req-roster__side">
              @if (($player['status'] ?? '') === 'confirmed')
                <span class="coach-req-pay-badge coach-req-pay-badge--paid">Confirmed</span>
              @else
                <span class="coach-req-pay-badge coach-req-pay-badge--pending">{{ ($player['role'] ?? '') === 'requester' ? 'Awaiting deposit' : 'Pending approval' }}</span>
                @if (empty($sessionRequestsIsAdmin ?? false))
                  <span class="coach-req-roster__actions">
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-confirm-player data-player-id="{{ $player['player_id'] ?? '' }}">Confirm place</button>
                    @if (($player['role'] ?? '') !== 'requester')
                      <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-player data-player-id="{{ $player['player_id'] ?? '' }}">Decline</button>
                    @endif
                  </span>
                @endif
              @endif
            </div>
          </div>
        @endforeach
      </div>

      @if (! empty($request['payment_note']))
        <p class="coach-req-card__coach-note">How to pay: {{ $request['payment_note'] }}</p>
      @endif

      @if (! empty($request['coach_note']))
        <p class="coach-req-card__coach-note">{{ $request['coach_note'] }}</p>
      @endif
      @if (empty($sessionRequestsIsAdmin ?? false))
        <div class="coach-req-card__actions">
          <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-adjust-details>View &amp; adjust details</button>
        </div>
        <p class="coach-req-card__hint">Families pay you directly. Confirm each player once you’ve received their deposit.</p>
      @else
        <p class="coach-req-card__hint">View only — the hosting coach can adjust this session.</p>
      @endif
    </div>
  @endif
</article>
