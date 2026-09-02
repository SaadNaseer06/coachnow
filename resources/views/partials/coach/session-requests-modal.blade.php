@php
  $requests = $sessionRequests ?? [];
  $openCount = collect($requests)->where('status', 'open')->count();
@endphp

<div
  id="coachSessionRequestsModal"
  class="coach-req-modal"
  aria-hidden="true"
  role="dialog"
  aria-labelledby="coachSessionRequestsTitle"
  aria-modal="true"
>
  <div class="coach-req-modal__backdrop" data-close-session-requests tabindex="-1"></div>

  <div class="coach-req-modal__panel">
    <header class="coach-req-modal__header">
      <div>
        <h2 id="coachSessionRequestsTitle">
          Session requests
          <span class="coach-req-badge" id="coachSessionRequestsModalBadge">{{ $openCount }} open</span>
        </h2>
        <p>First coach to accept hosts it — others can join until 30 minutes before start.</p>
      </div>
      <button type="button" class="coach-req-modal__close" data-close-session-requests aria-label="Close session requests">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </header>

    <div class="coach-req-modal__body">
      <div class="coach-req-list" id="coachSessionRequestsList">
        @foreach ($requests as $request)
          @include('partials.coach.session-request-card', ['request' => $request])
        @endforeach
      </div>
    </div>

    <footer class="coach-req-modal__footer">
      <p class="coach-req-footnote">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
        SMS alerts and live accept timers are in testing. Requests submitted on this device also appear here automatically.
      </p>
      <a href="{{ route('request-session') }}" class="admin-btn admin-btn-ghost admin-btn-sm" target="_blank" rel="noopener">View player flow</a>
    </footer>
  </div>
</div>
