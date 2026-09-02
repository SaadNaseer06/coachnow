@php
  $isOpen = ($request['status'] ?? 'open') === 'open';
  $isAccepted = ($request['status'] ?? '') === 'accepted';
@endphp

<article
  class="coach-req-card {{ $isAccepted ? 'is-accepted' : '' }} {{ $isOpen ? 'is-open' : '' }}"
  data-request-id="{{ $request['id'] ?? '' }}"
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
      <span class="coach-req-status coach-req-status--open"><span class="coach-req-pulse"></span> Open</span>
    @elseif ($isAccepted)
      <span class="coach-req-status coach-req-status--accepted">Accepted</span>
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
      <dd>{{ $request['session_type'] ?? '—' }}</dd>
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
  </div>

  @if (! empty($request['notes']))
    <p class="coach-req-card__notes">{{ $request['notes'] }}</p>
  @endif

  @if ($isOpen)
    <div class="coach-req-card__timer">
      <span class="coach-req-card__timer-label">Accept within</span>
      <span class="coach-req-countdown" data-countdown>—</span>
    </div>
    <div class="coach-req-card__actions">
      <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-accept-request>Accept &amp; host</button>
      <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-request>Decline</button>
    </div>
    <p class="coach-req-card__hint">If you accept, the player is notified and the request stays open for others to join until 30 minutes before start.</p>
  @elseif ($isAccepted)
    <div class="coach-req-card__joined">
      <span>{{ $request['players_joined'] ?? 1 }} player{{ ($request['players_joined'] ?? 1) > 1 ? 's' : '' }} joined</span>
      <span>Accepted by {{ $request['accepted_by'] ?? 'You' }}</span>
    </div>
  @endif
</article>
