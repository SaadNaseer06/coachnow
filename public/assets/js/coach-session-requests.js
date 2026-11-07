(() => {
  const SEEN_KEY = 'coachnow_session_requests_seen';

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  async function apiFetch(url, options = {}) {
    const headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
    };
    const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const error = new Error(payload.message || 'Request failed');
      error.payload = payload;
      throw error;
    }
    return payload;
  }

  const modal = document.getElementById('coachSessionRequestsModal');
  const list = document.getElementById('coachSessionRequestsList');
  const bell = document.getElementById('coachSessionRequestsBell');
  const badge = document.getElementById('coachSessionRequestsBadge');
  const modalBadge = document.getElementById('coachSessionRequestsModalBadge');
  const countEl = document.getElementById('coachOpenRequestCount');
  const kpi = document.getElementById('coachOpenRequestsKpi');
  const adjustOverlay = document.getElementById('coachAdjustOverlay');
  const adjustJoined = document.getElementById('coachAdjustJoined');
  const adjustMax = document.getElementById('coachAdjustMax');
  const adjustMin = document.getElementById('coachAdjustMin');
  const adjustLooking = document.getElementById('coachAdjustLooking');
  const adjustLookingHint = document.getElementById('coachAdjustLookingHint');
  const adjustNote = document.getElementById('coachAdjustNote');
  const adjustError = document.getElementById('coachAdjustError');
  const adjustSaveBtn = document.getElementById('coachAdjustSaveBtn');
  const adjustCancelBtn = document.getElementById('coachAdjustCancelBtn');
  const acceptOverlay = document.getElementById('coachAcceptOverlay');
  const acceptPrice = document.getElementById('coachAcceptPrice');
  const acceptNote = document.getElementById('coachAcceptNote');
  const acceptBudget = document.getElementById('coachAcceptBudget');
  const acceptError = document.getElementById('coachAcceptError');
  const acceptSaveBtn = document.getElementById('coachAcceptSaveBtn');
  const acceptCancelBtn = document.getElementById('coachAcceptCancelBtn');

  if (!modal || !list) return;

  let lastFocus = null;
  let adjustingCard = null;
  let acceptingId = null;
  let lookingManual = false;
  let cachedRequests = [];
  const currentCoachId = window.CoachNowCoachId != null ? Number(window.CoachNowCoachId) : null;

  const isAdminViewer = Boolean(window.CoachNowIsAdmin);
  const declinedKey = `coachnow_declined_requests_${currentCoachId || 'none'}`;

  function readDeclinedIds() {
    try {
      const stored = JSON.parse(localStorage.getItem(declinedKey) || '[]');
      return new Set(Array.isArray(stored) ? stored.map(String) : []);
    } catch {
      return new Set();
    }
  }

  function markDeclined(id) {
    if (!id) return;
    const ids = readDeclinedIds();
    ids.add(String(id));
    localStorage.setItem(declinedKey, JSON.stringify([...ids]));
  }

  // A request can go to several chosen coaches; requested_coach_id only holds the first.
  function sentToCurrentCoach(req) {
    const invited = (Array.isArray(req.invited_coach_ids) ? req.invited_coach_ids : []).map(Number);
    if (invited.length) return invited.includes(currentCoachId);
    const requestedId = req.requested_coach_id != null ? Number(req.requested_coach_id) : null;
    return requestedId == null || requestedId === currentCoachId;
  }

  function requestVisibleToCurrentCoach(req) {
    if (!req) return false;
    if (isAdminViewer) return true;
    if (currentCoachId == null) return false;
    if (readDeclinedIds().has(String(req.id))) return false;

    const hostId = req.host_coach_id != null ? Number(req.host_coach_id) : null;
    const status = req.status || 'open';

    if (hostId === currentCoachId) return true;
    if (status !== 'open') return false;
    return sentToCurrentCoach(req);
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatCountdown(seconds) {
    const s = Math.max(0, Math.floor(seconds));
    const d = Math.floor(s / 86400);
    const h = Math.floor((s % 86400) / 3600);
    const m = Math.floor((s % 3600) / 60);
    const r = s % 60;
    if (d > 0) return `${d}d ${h}h`;
    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}m`;
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

  function playersLabel(min, max) {
    if (min && max) return `${min}–${max}`;
    if (min) return `${min}+`;
    if (max) return `Up to ${max}`;
    return '';
  }

  function estimatePayout(priceRange, joined, { maxPlayers = '', minPlayers = '' } = {}) {
    const n = Number(joined || 0) || Number(maxPlayers || 0) || Number(minPlayers || 0);
    if (!n || !priceRange) return '';
    const basedOnJoined = Number(joined || 0) > 0;
    const text = String(priceRange);
    let m = text.match(/up to\s*\$?\s*(\d+)/i);
    if (m) {
      return `Up to $${(Number(m[1]) * n).toLocaleString()}${basedOnJoined ? ' est.' : ''} (${n} × up to $${m[1]})`;
    }
    m = text.match(/\$?\s*(\d+)\s*[–-]\s*\$?\s*(\d+)/);
    if (m) {
      return `$${(Number(m[1]) * n).toLocaleString()} – $${(Number(m[2]) * n).toLocaleString()}${basedOnJoined ? ' est.' : ''} (${n} × ${m[1]}–${m[2]})`;
    }
    m = text.match(/\$?\s*(\d+)\s*\+/);
    if (m) {
      return `$${(Number(m[1]) * n).toLocaleString()}+${basedOnJoined ? ' est.' : ''} (${n} × $${m[1]}+)`;
    }
    return '';
  }

  function payoutFieldHtml(req, joined) {
    const label = Number(joined || 0) > 0 ? 'Est. payout' : 'Est. if filled';
    const value = estimatePayout(req.price_range, joined, {
      maxPlayers: req.max_players ?? '',
      minPlayers: req.min_players ?? '',
    });
    if (!value) return '';
    return `<div><dt>${label}</dt><dd>${escapeHtml(value)}</dd></div>`;
  }

  function spotsLabel(joined, max, lookingFor) {
    const looking = lookingFor !== '' && lookingFor != null
      ? Number(lookingFor)
      : (max !== '' && max != null ? Math.max(0, Number(max) - Number(joined || 0)) : null);
    if (looking == null) return 'Open for more players';
    if (looking <= 0) return 'Full';
    return `Looking for ${looking} more`;
  }

  function parsePlayers(card) {
    try {
      const raw = card.dataset.players;
      const list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list : [];
    } catch {
      return [];
    }
  }

  // The requester keeps a place while the coach waits for their deposit.
  function placesTaken(players) {
    return players.filter((p) => p.status === 'confirmed' || p.role === 'requester').length;
  }

  function setPlayers(card, players) {
    card.dataset.players = JSON.stringify(players);
    card.dataset.playersJoined = String(placesTaken(players));
  }

  function activeCount() {
    return list.querySelectorAll('.coach-req-card.is-open, .coach-req-card.is-hosted').length;
  }

  function needsHostCount() {
    return list.querySelectorAll('.coach-req-card.is-open').length;
  }

  function updateBadges() {
    const needsHost = needsHostCount();
    const active = activeCount();
    const label = needsHost > 0 ? `${needsHost} need host` : `${active} active`;

    if (countEl) countEl.textContent = String(needsHost || active);
    if (modalBadge) modalBadge.textContent = label;

    if (badge) {
      badge.textContent = String(needsHost || active);
      badge.hidden = active === 0;
    }

    if (bell) {
      bell.classList.toggle('has-open', needsHost > 0);
    }
  }

  function updateCountdowns() {
    list.querySelectorAll('.coach-req-card.is-open').forEach((card) => {
      const el = card.querySelector('[data-countdown]');
      if (!el) return;
      // No date/time on the request → no deadline to count down to.
      if (!card.dataset.acceptExpires && !Number(card.dataset.acceptSeconds || 0)) {
        el.textContent = 'Before session';
        return;
      }
      const left = remainingSeconds(card);
      el.textContent = formatCountdown(left);
      if (left <= 0) {
        const id = card.dataset.requestId;
        writeStoredRequests(readStoredRequests().filter((item) => item.id !== id));
        card.remove();
      }
    });
    updateBadges();
  }

  function readStoredRequests() {
    return cachedRequests;
  }

  function writeStoredRequests(items) {
    cachedRequests = items.slice(0, 40);
  }

  function patchStoredRequest(id, patch) {
    const items = readStoredRequests();
    const idx = items.findIndex((item) => item.id === id);
    if (idx === -1) {
      items.unshift({ id, ...patch });
    } else {
      items[idx] = { ...items[idx], ...patch };
    }
    writeStoredRequests(items);
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

  function closeAdjustOverlay() {
    adjustingCard = null;
    lookingManual = false;
    if (adjustError) {
      adjustError.hidden = true;
      adjustError.textContent = '';
    }
    if (adjustOverlay) adjustOverlay.hidden = true;
  }

  function closeModal() {
    if (!modal.classList.contains('is-open')) return;
    closeAdjustOverlay();
    closeAcceptOverlay();
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    bell?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('coach-req-modal-open');
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  function contactHtml(player) {
    if (!player.email && !player.phone) return '';
    const phone = String(player.phone || '').replace(/[^0-9+]/g, '');
    return `<span class="coach-req-contact">
      ${player.email ? `<a href="mailto:${escapeHtml(player.email)}">${escapeHtml(player.email)}</a>` : ''}
      ${phone ? `<a href="tel:${escapeHtml(phone)}">${escapeHtml(player.phone)}</a>` : ''}
    </span>`;
  }

  function placeHtml(player) {
    if (player.status === 'confirmed') {
      return '<span class="coach-req-pay-badge coach-req-pay-badge--paid">Confirmed</span>';
    }
    const isRequester = player.role === 'requester';
    const label = isRequester ? 'Awaiting deposit' : 'Pending approval';
    const actions = isAdminViewer ? '' : `
      <span class="coach-req-roster__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-confirm-player data-player-id="${escapeHtml(player.player_id)}">Confirm place</button>
        ${isRequester ? '' : `<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-player data-player-id="${escapeHtml(player.player_id)}">Decline</button>`}
      </span>`;
    return `<span class="coach-req-pay-badge coach-req-pay-badge--pending">${label}</span>${actions}`;
  }

  function rosterHtml(players) {
    if (!players.length) {
      return '<p class="coach-req-roster__empty">No players yet.</p>';
    }
    return players.map((player) => `
      <div class="coach-req-roster__row">
        <div class="coach-req-roster__who">
          <div class="admin-person-fallback">${escapeHtml(player.initials || 'PL')}</div>
          <div>
            <strong>${escapeHtml(player.name || 'Player')}</strong>
            <span>${player.role === 'requester' ? 'Original requester' : 'Asked to join'}</span>
            ${contactHtml(player)}
          </div>
        </div>
        <div class="coach-req-roster__side">${placeHtml(player)}</div>
      </div>
    `).join('');
  }

  function renderHostedFooter(card, req) {
    const roster = Array.isArray(req.players) ? req.players : parsePlayers(card);
    const max = req.max_players ?? '';
    const min = req.min_players ?? '';
    const lookingFor = req.looking_for ?? '';
    const coachNote = req.coach_note || '';
    setPlayers(card, roster);
    card.dataset.maxPlayers = max === '' || max == null ? '' : String(max);
    card.dataset.minPlayers = min === '' || min == null ? '' : String(min);
    card.dataset.lookingFor = lookingFor === '' || lookingFor == null ? '' : String(lookingFor);
    card.dataset.coachNote = coachNote;

    const confirmedCount = roster.filter((p) => p.status === 'confirmed').length;
    const pendingCount = roster.filter((p) => p.status === 'pending' && p.role !== 'requester').length;

    const status = card.querySelector('.coach-req-status--hosted');
    if (status) {
      const looking = lookingFor === '' ? null : Number(lookingFor);
      const text = req.status === 'awaiting_deposit' ? 'Awaiting deposit' : (looking === 0 ? 'Full' : 'Open to join');
      status.innerHTML = `<span class="coach-req-pulse coach-req-pulse--green"></span> ${text}`;
    }

    const playersRow = card.querySelector('[data-players-row]');
    const playersField = card.querySelector('[data-field="players"]');
    const label = playersLabel(min, max);
    if (playersRow) playersRow.hidden = !label;
    if (playersField) playersField.textContent = label || '—';

    let hosted = card.querySelector('.coach-req-card__hosted');
    if (!hosted) {
      hosted = document.createElement('div');
      hosted.className = 'coach-req-card__hosted';
      card.appendChild(hosted);
    }

    hosted.innerHTML = `
      <div class="coach-req-card__hosted-meta">
        ${req.price_label ? `<span data-field="price">${escapeHtml(req.price_label)}</span>` : ''}
        <span data-field="confirmed">${confirmedCount} confirmed</span>
        <span data-field="pending">${pendingCount} pending</span>
        <span data-field="spots">${escapeHtml(spotsLabel(placesTaken(roster), max, lookingFor))}</span>
      </div>
      <div class="coach-req-roster" data-roster>${rosterHtml(roster)}</div>
      ${req.payment_note ? `<p class="coach-req-card__coach-note">How to pay: ${escapeHtml(req.payment_note)}</p>` : ''}
      ${coachNote ? `<p class="coach-req-card__coach-note">${escapeHtml(coachNote)}</p>` : ''}
      ${isAdminViewer ? '<p class="coach-req-card__hint">View only — the hosting coach can adjust this session.</p>' : `
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-adjust-details>View &amp; adjust details</button>
      </div>
      <p class="coach-req-card__hint">Families pay you directly. Confirm each player once you’ve received their deposit.</p>
      `}
    `;

    hosted.querySelector('[data-adjust-details]')?.addEventListener('click', () => openAdjustOverlay(card));
  }

  // "Up to $30 / player" or "$25 – $50 / player": start from the top of the parent's budget.
  function budgetAmount(priceRange) {
    const amounts = String(priceRange || '').match(/\d+/g);
    return amounts ? amounts[amounts.length - 1] : '';
  }

  function closeAcceptOverlay() {
    acceptingId = null;
    if (acceptError) {
      acceptError.hidden = true;
      acceptError.textContent = '';
    }
    if (acceptOverlay) acceptOverlay.hidden = true;
  }

  function showAcceptError(message) {
    if (!acceptError) return;
    acceptError.hidden = false;
    acceptError.textContent = message;
  }

  function openAcceptOverlay(card) {
    acceptingId = card.dataset.requestId;
    const budget = card.dataset.priceRange || '';
    if (acceptPrice) acceptPrice.value = budgetAmount(budget);
    if (acceptNote) acceptNote.value = modal.dataset.paymentNote || '';
    if (acceptBudget) {
      acceptBudget.hidden = !budget;
      acceptBudget.textContent = budget ? `The parent’s budget: ${budget}` : '';
    }
    if (acceptError) acceptError.hidden = true;
    if (acceptOverlay) {
      acceptOverlay.hidden = false;
      acceptPrice?.focus();
    }
  }

  function openAdjustOverlay(card) {
    adjustingCard = card;
    lookingManual = false;
    const joined = Number(card.dataset.playersJoined || 1) || 1;
    const max = card.dataset.maxPlayers || '';
    const min = card.dataset.minPlayers || '';
    let looking = card.dataset.lookingFor;
    if (looking === '' || looking == null) {
      looking = max !== '' ? String(Math.max(0, Number(max) - joined)) : '';
    }

    if (adjustJoined) adjustJoined.value = String(joined);
    if (adjustMax) adjustMax.value = max;
    if (adjustMin) adjustMin.value = min;
    if (adjustLooking) adjustLooking.value = looking;
    if (adjustNote) adjustNote.value = card.dataset.coachNote || '';
    if (adjustError) {
      adjustError.hidden = true;
      adjustError.textContent = '';
    }
    syncLookingHint();

    if (adjustOverlay) {
      adjustOverlay.hidden = false;
      adjustJoined?.focus();
    }
  }

  function syncLookingHint() {
    const joined = Number(adjustJoined?.value || 0);
    const max = adjustMax?.value;
    if (max !== '' && max != null && !lookingManual) {
      const auto = Math.max(0, Number(max) - joined);
      if (adjustLooking) adjustLooking.value = String(auto);
    }
    if (adjustLookingHint) {
      adjustLookingHint.textContent = max
        ? `Suggested: ${Math.max(0, Number(max) - joined)} more (max − joined).`
        : 'Set a max to auto-calculate spots needed.';
    }
  }

  function bindCardActions(card) {
    card.querySelector('[data-accept-request]')?.addEventListener('click', () => openAcceptOverlay(card));

    card.querySelector('[data-decline-request]')?.addEventListener('click', async () => {
      const btn = card.querySelector('[data-decline-request]');
      const id = card.dataset.requestId;
      if (!id) return;

      const ok = window.CoachNowDialog?.confirm
        ? await window.CoachNowDialog.confirm({
            title: 'Decline this request?',
            message: 'It will leave your inbox. If it was sent only to you, the player is told it was declined.',
            confirmLabel: 'Decline',
            cancelLabel: 'Keep it',
          })
        : false;
      if (!ok) return;

      window.CoachNowBusy?.setBusy(btn, { label: 'Declining…' });
      try {
        await apiFetch(`/api/session-requests/${encodeURIComponent(id)}/decline`, {
          method: 'POST',
          body: JSON.stringify({}),
        });
        markDeclined(id);
        writeStoredRequests(readStoredRequests().filter((item) => item.id !== id));
        card.remove();
        updateCountdowns();
        updateBadges();
      } catch (error) {
        window.CoachNowBusy?.clearBusy(btn);
        if (window.CoachNowDialog?.alert) {
          await window.CoachNowDialog.alert({
            title: 'Decline failed',
            message: error.message || 'Could not decline this request.',
          });
        }
      }
    });

    card.querySelector('[data-adjust-details]')?.addEventListener('click', () => openAdjustOverlay(card));
  }

  function canAcceptRequest(req) {
    if (isAdminViewer) return false;
    if (currentCoachId == null) return false;
    const status = req.status || 'open';
    if (status !== 'open') return false;
    if (req.acceptExpiresAt && Number(req.acceptExpiresAt) <= Date.now()) return false;
    return sentToCurrentCoach(req);
  }
  function extraFieldsHtml(req) {
    const parts = [];
    const label = playersLabel(req.min_players, req.max_players);
    if (label) {
      parts.push(`<div data-players-row><dt>Players</dt><dd data-field="players">${escapeHtml(label)}</dd></div>`);
    } else {
      parts.push('<div data-players-row hidden><dt>Players</dt><dd data-field="players">—</dd></div>');
    }
    if (req.player_level) parts.push(`<div><dt>Level</dt><dd>${escapeHtml(req.player_level)}</dd></div>`);
    if (req.know_by) parts.push(`<div><dt>Need to know by</dt><dd>${escapeHtml(req.know_by)}</dd></div>`);
    return parts.join('');
  }

  function preferredCoachName(req) {
    return String(req?.requested_coach || '').trim();
  }

  function isTargetedRequest(req) {
    if (!req) return false;
    return preferredCoachName(req) !== '' || req.requested_coach_id != null;
  }

  function requestedCoachBannerHtml(req) {
    if (!isTargetedRequest(req)) {
      return '<p class="coach-req-card__target coach-req-card__target--open">Open to any coach</p>';
    }
    if (currentCoachId != null && sentToCurrentCoach(req)) {
      return '<p class="coach-req-card__target">Requested for you</p>';
    }
    const name = preferredCoachName(req) || 'Selected coach';
    return `<p class="coach-req-card__target">Preferred coach · ${escapeHtml(name)}</p>`;
  }

  function preferredCoachFieldHtml(req) {
    const name = isTargetedRequest(req)
      ? (preferredCoachName(req) || 'Selected coach')
      : 'Any coach';
    return `<div><dt>Preferred coach</dt><dd>${escapeHtml(name)}</dd></div>`;
  }

  function buildCard(req) {
    const card = document.createElement('article');
    const hosted = ['accepted', 'confirmed', 'awaiting_deposit', 'hosted'].includes(req.status);
    const players = Array.isArray(req.players) ? req.players : [];
    card.className = `coach-req-card ${hosted ? 'is-hosted' : 'is-open'}`;
    card.dataset.requestId = req.id;
    card.dataset.minPlayers = req.min_players ?? '';
    card.dataset.maxPlayers = req.max_players ?? '';
    card.dataset.playersJoined = String(players.length || req.players_joined || (hosted ? 1 : 0));
    card.dataset.lookingFor = req.looking_for ?? '';
    card.dataset.coachNote = req.coach_note || '';
    card.dataset.sessionType = req.session_type || '';
    card.dataset.priceRange = req.price_range || '';
    card.dataset.requestedCoachId = req.requested_coach_id != null ? String(req.requested_coach_id) : '';
    // Real deadline from the server (parent's cutoff, or 1h before the session for "Anytime").
    card.dataset.acceptSeconds = String(req.accept_seconds || 0);
    card.dataset.countdownStarted = String(Date.now());
    setPlayers(card, players);
    if (req.acceptExpiresAt) card.dataset.acceptExpires = String(req.acceptExpiresAt);
    else delete card.dataset.acceptExpires;

    if (hosted) {
      card.innerHTML = `
        <div class="coach-req-card__top">
          <div class="coach-req-card__player">
            <div class="admin-person-fallback">${escapeHtml(req.initials || 'NR')}</div>
            <div>
              <strong>${escapeHtml(req.name || 'New session request')}</strong>
              <span>${escapeHtml(req.posted || 'Just now')} · ${escapeHtml(req.id || '')}</span>
            </div>
          </div>
          <span class="coach-req-status coach-req-status--hosted"><span class="coach-req-pulse coach-req-pulse--green"></span> Open to join</span>
        </div>
        ${requestedCoachBannerHtml(req)}
        <div class="coach-req-card__grid">
          <div><dt>When</dt><dd>${escapeHtml(req.when || '—')}</dd></div>
          <div><dt>Location</dt><dd>${escapeHtml(req.location || '—')}<span>${escapeHtml(req.city || '')}</span></dd></div>
          <div><dt>Session type</dt><dd data-field="session_type">${escapeHtml(req.session_type || '—')}</dd></div>
          ${preferredCoachFieldHtml(req)}
          <div><dt>Age range</dt><dd>${escapeHtml(req.age_range || '—')}</dd></div>
          <div><dt>Budget / player</dt><dd>${escapeHtml(req.price_range || '—')}</dd></div>
          ${payoutFieldHtml(req, players.length || Number(card.dataset.playersJoined || 0))}
          <div><dt>Sport</dt><dd>${escapeHtml(req.sport || '—')}</dd></div>
          ${extraFieldsHtml(req)}
        </div>
        ${req.notes ? `<p class="coach-req-card__notes">${escapeHtml(req.notes)}</p>` : ''}
      `;
      renderHostedFooter(card, req);
      return card;
    }

    card.innerHTML = `
      <div class="coach-req-card__top">
        <div class="coach-req-card__player">
          <div class="admin-person-fallback">${escapeHtml(req.initials || 'NR')}</div>
          <div>
            <strong>${escapeHtml(req.name || 'New session request')}</strong>
            <span>${escapeHtml(req.posted || 'Just now')} · ${escapeHtml(req.id || '')}</span>
          </div>
        </div>
        <span class="coach-req-status coach-req-status--open"><span class="coach-req-pulse"></span> Needs host</span>
      </div>
      ${requestedCoachBannerHtml(req)}
      <div class="coach-req-card__grid">
        <div><dt>When</dt><dd>${escapeHtml(req.when || '—')}</dd></div>
        <div><dt>Location</dt><dd>${escapeHtml(req.location || '—')}<span>${escapeHtml(req.city || '')}</span></dd></div>
        <div><dt>Session type</dt><dd data-field="session_type">${escapeHtml(req.session_type || '—')}</dd></div>
        ${preferredCoachFieldHtml(req)}
        <div><dt>Age range</dt><dd>${escapeHtml(req.age_range || '—')}</dd></div>
        <div><dt>Budget / player</dt><dd>${escapeHtml(req.price_range || '—')}</dd></div>
        ${payoutFieldHtml(req, 0)}
        <div><dt>Sport</dt><dd>${escapeHtml(req.sport || '—')}</dd></div>
        ${extraFieldsHtml(req)}
      </div>
      ${req.notes ? `<p class="coach-req-card__notes">${escapeHtml(req.notes)}</p>` : ''}
      <div class="coach-req-card__timer">
        <span class="coach-req-card__timer-label">Accept by</span>
        <span class="coach-req-countdown" data-countdown>—</span>
      </div>
      ${canAcceptRequest(req) ? `
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-accept-request data-loading-text="Accepting…">Accept &amp; host</button>
        <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-request data-loading-text="Declining…">Decline</button>
      </div>
      <p class="coach-req-card__hint">Accept to set your price. You and the family arrange payment directly; CoachNow doesn’t collect it.</p>
      ` : `<p class="coach-req-card__hint">View only — this request is waiting for ${escapeHtml(preferredCoachName(req) || 'a coach')} to accept.</p>`}
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

  function refreshHostedFromStorage() {
    const stored = readStoredRequests();
    list.querySelectorAll('.coach-req-card.is-hosted').forEach((card) => {
      const match = stored.find((item) => item.id === card.dataset.requestId);
      if (!match) return;
      renderHostedFooter(card, match);
    });
    updateBadges();
  }

  async function loadLiveRequests() {
    try {
      const payload = await apiFetch('/api/session-requests');
      const requests = (Array.isArray(payload.data) ? payload.data : [])
        .filter(requestVisibleToCurrentCoach);
      writeStoredRequests(requests);

      const seen = getSeenIds();
      const newIds = [];
      const existingIds = new Set(
        [...list.querySelectorAll('.coach-req-card')].map((c) => c.dataset.requestId).filter(Boolean)
      );

      list.innerHTML = '';
      requests.forEach((req) => {
        if (!req?.id) return;
        list.appendChild(buildCard(req));
        if ((!req.status || req.status === 'open') && !seen.has(req.id) && !existingIds.has(req.id)) {
          newIds.push(req.id);
        }
      });

      if (newIds.length) {
        markSeen(newIds);
        openModal();
        bell?.classList.add('coach-req-bell--pulse');
        window.setTimeout(() => bell?.classList.remove('coach-req-bell--pulse'), 2400);
      }

      refreshHostedFromStorage();
      updateCountdowns();
      updateBadges();
    } catch {
      /* keep server-rendered cards if API fails */
    }
  }

  adjustJoined?.addEventListener('input', syncLookingHint);
  adjustMax?.addEventListener('input', () => {
    lookingManual = false;
    syncLookingHint();
  });
  adjustLooking?.addEventListener('input', () => {
    lookingManual = true;
  });

  adjustSaveBtn?.addEventListener('click', async () => {
    if (!adjustingCard) return;
    const joined = Number(adjustJoined?.value || 0);
    const max = adjustMax?.value === '' ? '' : Number(adjustMax.value);
    const min = adjustMin?.value === '' ? '' : Number(adjustMin.value);
    const looking = adjustLooking?.value === '' ? '' : Number(adjustLooking.value);
    const note = adjustNote?.value.trim() || '';

    if (!joined || joined < 1) {
      if (adjustError) {
        adjustError.hidden = false;
        adjustError.textContent = 'Players joined must be at least 1.';
      }
      return;
    }
    if (max !== '' && max < joined) {
      if (adjustError) {
        adjustError.hidden = false;
        adjustError.textContent = 'Max players must be greater than or equal to players joined.';
      }
      return;
    }
    if (min !== '' && max !== '' && min > max) {
      if (adjustError) {
        adjustError.hidden = false;
        adjustError.textContent = 'Min players cannot be greater than max players.';
      }
      return;
    }

    const id = adjustingCard.dataset.requestId;
    if (!id) return;

    window.CoachNowBusy?.setBusy(adjustSaveBtn, { label: 'Saving…' });

    try {
      const payload = await apiFetch(`/api/session-requests/${encodeURIComponent(id)}`, {
        method: 'PATCH',
        body: JSON.stringify({
          max_players: max === '' ? null : max,
          min_players: min === '' ? null : min,
          looking_for: looking === '' ? null : looking,
          coach_note: note || null,
        }),
      });
      const req = payload.data;
      patchStoredRequest(id, req);
      renderHostedFooter(adjustingCard, req);
      window.CoachNowBusy?.clearBusy(adjustSaveBtn);
      closeAdjustOverlay();
      updateBadges();
    } catch (error) {
      window.CoachNowBusy?.clearBusy(adjustSaveBtn);
      if (adjustError) {
        adjustError.hidden = false;
        adjustError.textContent = error.message || 'Could not save details.';
      }
    }
  });

  adjustCancelBtn?.addEventListener('click', closeAdjustOverlay);

  acceptCancelBtn?.addEventListener('click', closeAcceptOverlay);
  acceptPrice?.addEventListener('input', () => {
    if (acceptError) acceptError.hidden = true;
  });

  acceptSaveBtn?.addEventListener('click', async () => {
    const id = acceptingId;
    if (!id) return;
    const price = acceptPrice?.value.trim() ?? '';
    if (price === '' || Number(price) < 0) {
      showAcceptError('Enter your price per player.');
      acceptPrice?.focus();
      return;
    }

    const note = acceptNote?.value.trim() || '';
    window.CoachNowBusy?.setBusy(acceptSaveBtn, { label: 'Accepting…' });
    try {
      const payload = await apiFetch(`/api/session-requests/${encodeURIComponent(id)}/accept`, {
        method: 'POST',
        body: JSON.stringify({ price_per_player: Number(price), payment_note: note || null }),
      });
      const req = payload.data;
      patchStoredRequest(id, req);
      if (note) modal.dataset.paymentNote = note;
      list.querySelector(`[data-request-id="${CSS.escape(id)}"]`)?.remove();
      list.prepend(buildCard(req));
      closeAcceptOverlay();
      updateBadges();
      updateCountdowns();
    } catch (error) {
      showAcceptError(error.message || 'Could not accept this request.');
      // Another coach got there first, so the card shouldn't stay.
      loadLiveRequests();
    } finally {
      window.CoachNowBusy?.clearBusy(acceptSaveBtn);
    }
  });

  // Roster buttons are redrawn on every refresh, so listen on the list.
  list.addEventListener('click', async (event) => {
    const btn = event.target.closest('[data-confirm-player], [data-decline-player]');
    const card = btn?.closest('.coach-req-card');
    if (!btn || !card) return;

    const confirming = btn.hasAttribute('data-confirm-player');
    if (!confirming) {
      const ok = window.CoachNowDialog?.confirm
        ? await window.CoachNowDialog.confirm({
            title: 'Decline this player?',
            message: 'They’ll be told you couldn’t confirm their place.',
            confirmLabel: 'Decline',
            cancelLabel: 'Keep them',
          })
        : window.confirm('Decline this player?');
      if (!ok) return;
    }

    const id = card.dataset.requestId;
    const action = confirming ? 'confirm' : 'decline';
    window.CoachNowBusy?.setBusy(btn, { label: confirming ? 'Confirming…' : 'Declining…' });
    try {
      const payload = await apiFetch(`/api/session-requests/${encodeURIComponent(id)}/players/${encodeURIComponent(btn.dataset.playerId)}/${action}`, {
        method: 'POST',
        body: JSON.stringify({}),
      });
      patchStoredRequest(id, payload.data);
      // Look it up again: a refresh or a second confirm may have redrawn the card by now.
      list.querySelector(`[data-request-id="${CSS.escape(id)}"]`)?.replaceWith(buildCard(payload.data));
      updateBadges();
    } catch (error) {
      window.CoachNowBusy?.clearBusy(btn);
      if (window.CoachNowDialog?.alert) {
        await window.CoachNowDialog.alert({
          title: confirming ? 'Could not confirm' : 'Could not decline',
          message: error.message || 'Please try again.',
        });
      } else {
        window.alert(error.message || 'Please try again.');
      }
    }
  });

  bell?.addEventListener('click', openModal);
  kpi?.addEventListener('click', openModal);
  document.querySelectorAll('[data-open-session-requests]').forEach((el) => {
    el.addEventListener('click', openModal);
  });
  // Deep link from Training Near You: /coach/dashboard?requests=open
  if (new URLSearchParams(window.location.search).get('requests') === 'open') {
    openModal();
  }
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
    if (event.key !== 'Escape') return;
    if (acceptOverlay && !acceptOverlay.hidden) {
      closeAcceptOverlay();
      return;
    }
    if (adjustOverlay && !adjustOverlay.hidden) {
      closeAdjustOverlay();
      return;
    }
    if (modal.classList.contains('is-open')) closeModal();
  });

  list.querySelectorAll('.coach-req-card').forEach((card) => {
    if (!card.dataset.countdownStarted && card.dataset.acceptSeconds && card.classList.contains('is-open')) {
      card.dataset.countdownStarted = String(Date.now());
    }
    bindCardActions(card);
  });

  loadLiveRequests();
  updateCountdowns();
  setInterval(() => {
    updateCountdowns();
  }, 1500);

  const realtimeEnabled = Boolean(window.CoachNowRealtime?.enabled && window.Echo);
  function onSessionRequestChanged(payload) {
    const action = payload?.action || 'updated';
    const incoming = payload?.request || payload;
    loadLiveRequests().then(() => {
      if (action === 'created' && requestVisibleToCurrentCoach(incoming)) {
        openModal();
        bell?.classList.add('coach-req-bell--pulse');
        window.setTimeout(() => bell?.classList.remove('coach-req-bell--pulse'), 2400);
      }
    });
  }

  if (realtimeEnabled) {
    window.Echo.channel('coaches.session-requests')
      .listen('.session-request.changed', onSessionRequestChanged)
      .error((err) => {
        console.warn('Pusher channel error', err);
      });
    if (currentCoachId != null) {
      window.Echo.private(`coach.session-requests.${currentCoachId}`)
        .listen('.session-request.changed', onSessionRequestChanged)
        .error((err) => {
          console.warn('Pusher private channel error', err);
        });
    }
    setInterval(loadLiveRequests, 90000);
  } else {
    if (window.CoachNowRealtime?.reason) {
      console.warn('[CoachNow realtime disabled]', window.CoachNowRealtime.reason);
    }
    setInterval(loadLiveRequests, 30000);
  }
})();
