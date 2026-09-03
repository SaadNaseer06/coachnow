@php
  $status = $request['status'] ?? 'open';
  $isOpen = $status === 'open';
  $isHosted = in_array($status, ['accepted', 'confirmed', 'awaiting_deposit', 'hosted'], true);
  $players = $request['players'] ?? [];
  if ($players === [] && $isHosted) {
      $players = [[
          'name' => $request['name'] ?? 'Player',
          'initials' => $request['initials'] ?? '??',
          'role' => 'requester',
          'paid' => ! empty($request['deposit_paid']),
          'paid_with' => $request['paid_with'] ?? '',
      ]];
  }
  $joined = count($players) ?: (int) ($request['players_joined'] ?? ($isHosted ? 1 : 0));
  $paidCount = collect($players)->where('paid', true)->count();
  $pendingCount = max(0, $joined - $paidCount);
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

  $priceRaw = (string) ($request['price_range'] ?? '');
  $estCount = $joined > 0 ? $joined : (int) ($maxPlayers !== '' ? $maxPlayers : ($minPlayers !== '' ? $minPlayers : 0));
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
  data-players='@json($players)'
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
    <p class="coach-req-card__hint">If you accept, the $10 deposit is charged to the parent’s card on file. You don’t handle payment — you just see who’s confirmed and paid.</p>
  @elseif ($isHosted)
    <div class="coach-req-card__hosted">
      <div class="coach-req-card__hosted-meta">
        <span data-field="joined">{{ $joined }} player{{ $joined === 1 ? '' : 's' }} joined</span>
        <span data-field="paid">{{ $paidCount }} paid</span>
        <span data-field="pending">{{ $pendingCount }} pending deposit</span>
        <span data-field="spots">
          @if ((int) $lookingFor > 0)
            Looking for {{ $lookingFor }} more
          @elseif ($maxPlayers !== '' && $joined >= (int) $maxPlayers)
            Full
          @else
            Open for more players
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
                <span>{{ ($player['role'] ?? '') === 'requester' ? 'Original requester' : 'Joined' }}</span>
              </div>
            </div>
            @if (! empty($player['paid']))
              <span class="coach-req-pay-badge coach-req-pay-badge--paid">Paid{{ ! empty($player['paid_with']) ? ' · '.$player['paid_with'] : '' }}</span>
            @elseif (! empty($player['card_on_file']))
              <span class="coach-req-pay-badge coach-req-pay-badge--pending">Card on file</span>
            @else
              <span class="coach-req-pay-badge coach-req-pay-badge--pending">Deposit pending</span>
            @endif
          </div>
        @endforeach
      </div>

      @if (! empty($request['coach_note']))
        <p class="coach-req-card__coach-note">{{ $request['coach_note'] }}</p>
      @endif
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-adjust-details>View &amp; adjust details</button>
      </div>
      <p class="coach-req-card__hint">Deposits are handled by parents/players. You only see who’s confirmed and paid.</p>
    </div>
  @endif
</article>
