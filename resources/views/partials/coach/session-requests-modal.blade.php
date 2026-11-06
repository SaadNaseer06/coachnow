@php
  $requests = $sessionRequests ?? [];
  $openCount = collect($requests)->where('status', 'open')->count();
@endphp

<div
  id="coachSessionRequestsModal"
  class="coach-req-modal"
  data-payment-note="{{ $coachPaymentNote ?? '' }}"
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
          <span class="coach-req-badge" id="coachSessionRequestsModalBadge">{{ $openCount }} need host</span>
        </h2>
        <p>Accept to set your price and host. Families pay you directly, and you confirm each player once you’ve received their deposit.</p>
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
        Only requests you can still accept appear here. Expired requests drop off automatically.
      </p>
    </footer>
  </div>

  <div id="coachAcceptOverlay" class="coach-deposit" hidden>
    <div class="coach-deposit__card coach-adjust-card" role="dialog" aria-labelledby="coachAcceptTitle" aria-modal="true">
      <p class="coach-deposit__kicker">Accept &amp; host</p>
      <h3 id="coachAcceptTitle">Set your price</h3>
      <p class="coach-deposit__copy">Families see this price and pay you directly. CoachNow doesn’t collect it.</p>

      <div class="coach-adjust-grid">
        <div class="coach-adjust-field coach-adjust-field--full">
          <label for="coachAcceptPrice">Price per player ($)</label>
          <input type="number" id="coachAcceptPrice" min="0" max="1000" step="1" inputmode="decimal" placeholder="e.g. 25">
          <p class="coach-adjust-hint" id="coachAcceptBudget" hidden></p>
        </div>
        <div class="coach-adjust-field coach-adjust-field--full">
          <label for="coachAcceptNote">How should families pay? <span>(optional)</span></label>
          <textarea id="coachAcceptNote" rows="2" maxlength="500" placeholder="e.g. Venmo @your-name, $10 deposit to hold your place"></textarea>
          <p class="coach-adjust-hint">We’ll remember this for your next request.</p>
        </div>
      </div>

      <p class="pay-methods__error" id="coachAcceptError" hidden></p>
      <button type="button" class="admin-btn admin-btn-primary" id="coachAcceptSaveBtn">Accept request</button>
      <button type="button" class="admin-btn admin-btn-ghost" id="coachAcceptCancelBtn">Cancel</button>
    </div>
  </div>

  <div id="coachAdjustOverlay" class="coach-deposit" hidden>
    <div class="coach-deposit__card coach-adjust-card" role="dialog" aria-labelledby="coachAdjustTitle" aria-modal="true">
      <p class="coach-deposit__kicker">You’re hosting</p>
      <h3 id="coachAdjustTitle">View &amp; adjust details</h3>
      <p class="coach-deposit__copy">Update group size so the session stays open for the right number of players. Example: 3 joined, max 5 — looking for 2 more.</p>

      <div class="coach-adjust-grid">
        <div class="coach-adjust-field">
          <label for="coachAdjustJoined">Places taken</label>
          <input type="number" id="coachAdjustJoined" min="1" max="30" inputmode="numeric" readonly>
        </div>
        <div class="coach-adjust-field">
          <label for="coachAdjustMax">Max players</label>
          <input type="number" id="coachAdjustMax" min="1" max="30" inputmode="numeric" placeholder="e.g. 5">
        </div>
        <div class="coach-adjust-field">
          <label for="coachAdjustMin">Min players <span>(optional)</span></label>
          <input type="number" id="coachAdjustMin" min="1" max="30" inputmode="numeric" placeholder="e.g. 3">
        </div>
        <div class="coach-adjust-field coach-adjust-field--full">
          <label for="coachAdjustLooking">Looking for</label>
          <input type="number" id="coachAdjustLooking" min="0" max="30" inputmode="numeric" placeholder="Spots still needed">
          <p class="coach-adjust-hint" id="coachAdjustLookingHint">Auto-fills from max − joined when you change those fields.</p>
        </div>
        <div class="coach-adjust-field coach-adjust-field--full">
          <label for="coachAdjustNote">Note for joiners <span>(optional)</span></label>
          <textarea id="coachAdjustNote" rows="2" placeholder="e.g. Need 2 more U10 players for SAQ"></textarea>
        </div>
      </div>

      <p class="pay-methods__error" id="coachAdjustError" hidden></p>
      <button type="button" class="admin-btn admin-btn-primary" id="coachAdjustSaveBtn">Save details</button>
      <button type="button" class="admin-btn admin-btn-ghost" id="coachAdjustCancelBtn">Cancel</button>
    </div>
  </div>
</div>
