(() => {
  const STORAGE_KEY = 'coachnow_session_requests';
  const SEEN_KEY = 'coachnow_session_requests_seen';

  const modal = document.getElementById('coachSessionRequestsModal');
  const list = document.getElementById('coachSessionRequestsList');
  const bell = document.getElementById('coachSessionRequestsBell');
  const badge = document.getElementById('coachSessionRequestsBadge');
  const modalBadge = document.getElementById('coachSessionRequestsModalBadge');
  const countEl = document.getElementById('coachOpenRequestCount');
  const kpi = document.getElementById('coachOpenRequestsKpi');

  if (!modal || !list) return;

  let lastFocus = null;

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatCountdown(seconds) {
    const s = Math.max(0, Math.floor(seconds));
    const m = Math.floor(s / 60);
    const r = s % 60;
    return `${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
  }

  function remainingSeconds(card) {
    const stored = card.dataset.acceptExpires;
    if (stored) {
      return Math.max(0, Math.floor((Number(stored) - Date.now()) / 1000));
    }
    const initial = Number(card.dataset.acceptSeconds || 0);
    if (!initial) return 0;
    const started = Number(card.dataset.countdownStarted || Date.now());
    return Math.max(0, initial - Math.floor((Date.now() - started) / 1000));
  }

  function openCount() {
    return list.querySelectorAll('.coach-req-card.is-open').length;
  }

  function updateBadges() {
    const count = openCount();
    const label = `${count} open`;

    if (countEl) countEl.textContent = String(count);
    if (modalBadge) modalBadge.textContent = label;

    if (badge) {
      badge.textContent = String(count);
      badge.hidden = count === 0;
    }

    if (bell) {
      bell.classList.toggle('has-open', count > 0);
    }
  }

  function updateCountdowns() {
    list.querySelectorAll('.coach-req-card.is-open').forEach((card) => {
      const el = card.querySelector('[data-countdown]');
      if (!el) return;
      const left = remainingSeconds(card);
      el.textContent = formatCountdown(left);
      if (left <= 0) {
        card.classList.remove('is-open');
        card.classList.add('is-expired');
        const status = card.querySelector('.coach-req-status');
        if (status) {
          status.className = 'coach-req-status';
          status.textContent = 'Expired';
        }
        card.querySelector('.coach-req-card__actions')?.remove();
      }
    });
    updateBadges();
  }

  function openModal() {
    if (modal.classList.contains('is-open')) return;
    lastFocus = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    bell?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('coach-req-modal-open');
    modal.querySelector('.coach-req-modal__close')?.focus();
  }

  function closeModal() {
    if (!modal.classList.contains('is-open')) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    bell?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('coach-req-modal-open');
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  function bindCardActions(card) {
    card.querySelector('[data-accept-request]')?.addEventListener('click', () => {
      card.classList.remove('is-open');
      card.classList.add('is-accepted');
      const status = card.querySelector('.coach-req-status');
      if (status) {
        status.className = 'coach-req-status coach-req-status--accepted';
        status.textContent = 'Accepted';
      }
      card.querySelector('.coach-req-card__timer')?.remove();
      card.querySelector('.coach-req-card__actions')?.remove();
      card.querySelector('.coach-req-card__hint')?.remove();

      const joined = document.createElement('div');
      joined.className = 'coach-req-card__joined';
      joined.innerHTML = '<span>1 player joined</span><span>Accepted by You</span>';
      card.appendChild(joined);
      updateCountdowns();
    });

    card.querySelector('[data-decline-request]')?.addEventListener('click', () => {
      card.remove();
      updateCountdowns();
    });
  }

  function buildCard(req) {
    const card = document.createElement('article');
    card.className = 'coach-req-card is-open';
    card.dataset.requestId = req.id;
    card.dataset.acceptSeconds = String(req.accept_seconds || 900);
    card.dataset.countdownStarted = String(Date.now());
    if (req.acceptExpiresAt) card.dataset.acceptExpires = String(req.acceptExpiresAt);

    card.innerHTML = `
      <div class="coach-req-card__top">
        <div class="coach-req-card__player">
          <div class="admin-person-fallback">${escapeHtml(req.initials || 'NR')}</div>
          <div>
            <strong>${escapeHtml(req.name || 'New session request')}</strong>
            <span>${escapeHtml(req.posted || 'Just now')} · ${escapeHtml(req.id || '')}</span>
          </div>
        </div>
        <span class="coach-req-status coach-req-status--open"><span class="coach-req-pulse"></span> Open</span>
      </div>
      <div class="coach-req-card__grid">
        <div><dt>When</dt><dd>${escapeHtml(req.when || '—')}</dd></div>
        <div><dt>Location</dt><dd>${escapeHtml(req.location || '—')}<span>${escapeHtml(req.city || '')}</span></dd></div>
        <div><dt>Session type</dt><dd>${escapeHtml(req.session_type || '—')}</dd></div>
        <div><dt>Age range</dt><dd>${escapeHtml(req.age_range || '—')}</dd></div>
        <div><dt>Price / player</dt><dd>${escapeHtml(req.price_range || '—')}</dd></div>
        <div><dt>Sport</dt><dd>${escapeHtml(req.sport || 'Soccer')}</dd></div>
      </div>
      ${req.notes ? `<p class="coach-req-card__notes">${escapeHtml(req.notes)}</p>` : ''}
      <div class="coach-req-card__timer">
        <span class="coach-req-card__timer-label">Accept within</span>
        <span class="coach-req-countdown" data-countdown>—</span>
      </div>
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-accept-request>Accept &amp; host</button>
        <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-request>Decline</button>
      </div>
      <p class="coach-req-card__hint">If you accept, the player is notified and the request stays open for others to join until 30 minutes before start.</p>
    `;

    bindCardActions(card);
    return card;
  }

  function getSeenIds() {
    try {
      return new Set(JSON.parse(sessionStorage.getItem(SEEN_KEY) || '[]'));
    } catch {
      return new Set();
    }
  }

  function markSeen(ids) {
    const seen = getSeenIds();
    ids.forEach((id) => seen.add(id));
    sessionStorage.setItem(SEEN_KEY, JSON.stringify([...seen]));
  }

  function loadLiveRequests() {
    let stored = [];
    try {
      stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
      stored = [];
    }

    const staticIds = new Set(
      [...list.querySelectorAll('.coach-req-card')].map((c) => c.dataset.requestId).filter(Boolean)
    );

    const seen = getSeenIds();
    const newIds = [];

    stored.forEach((req) => {
      if (!req?.id || staticIds.has(req.id)) return;
      list.prepend(buildCard(req));
      if (!seen.has(req.id)) newIds.push(req.id);
    });

    if (newIds.length) {
      markSeen(newIds);
      openModal();
      bell?.classList.add('coach-req-bell--pulse');
      window.setTimeout(() => bell?.classList.remove('coach-req-bell--pulse'), 2400);
    }

    updateCountdowns();
  }

  bell?.addEventListener('click', openModal);

  kpi?.addEventListener('click', openModal);
  kpi?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      openModal();
    }
  });

  modal.querySelectorAll('[data-close-session-requests]').forEach((el) => {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });

  list.querySelectorAll('.coach-req-card').forEach((card) => {
    if (!card.dataset.countdownStarted && card.dataset.acceptSeconds) {
      card.dataset.countdownStarted = String(Date.now());
    }
    bindCardActions(card);
  });

  loadLiveRequests();
  updateCountdowns();
  setInterval(updateCountdowns, 1000);

  window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY) loadLiveRequests();
  });
})();
