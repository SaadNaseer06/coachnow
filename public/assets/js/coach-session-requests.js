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

  if (!modal || !list) return;

  let lastFocus = null;
  let adjustingCard = null;
  let lookingManual = false;

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

  function setPlayers(card, players) {
    card.dataset.players = JSON.stringify(players);
    card.dataset.playersJoined = String(players.length);
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
        card.querySelector('.coach-req-card__timer')?.remove();
      }
    });
    updateBadges();
  }

  function readStoredRequests() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
      return [];
    }
  }

  function writeStoredRequests(items) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0, 20)));
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
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    bell?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('coach-req-modal-open');
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  function rosterHtml(players) {
    if (!players.length) {
      return '<p class="coach-req-roster__empty">No players confirmed yet.</p>';
    }
    return players.map((player) => `
      <div class="coach-req-roster__row">
        <div class="coach-req-roster__who">
          <div class="admin-person-fallback">${escapeHtml(player.initials || 'PL')}</div>
          <div>
            <strong>${escapeHtml(player.name || 'Player')}</strong>
            <span>${player.role === 'requester' ? 'Original requester' : 'Joined'}</span>
          </div>
        </div>
        ${player.paid
          ? `<span class="coach-req-pay-badge coach-req-pay-badge--paid">Paid${player.paid_with ? ` · ${escapeHtml(player.paid_with)}` : ''}</span>`
          : player.card_on_file
            ? `<span class="coach-req-pay-badge coach-req-pay-badge--pending">Card on file</span>`
            : '<span class="coach-req-pay-badge coach-req-pay-badge--pending">Deposit pending</span>'}
      </div>
    `).join('');
  }

  function renderHostedFooter(card, { joined, max, min, lookingFor, coachNote, players }) {
    const roster = players || parsePlayers(card);
    setPlayers(card, roster);
    card.dataset.maxPlayers = max === '' || max == null ? '' : String(max);
    card.dataset.minPlayers = min === '' || min == null ? '' : String(min);
    card.dataset.lookingFor = lookingFor === '' || lookingFor == null ? '' : String(lookingFor);
    card.dataset.coachNote = coachNote || '';

    const paidCount = roster.filter((p) => p.paid).length;
    const pendingCount = Math.max(0, roster.length - paidCount);

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
        <span data-field="joined">${roster.length || joined} player${(roster.length || joined) === 1 ? '' : 's'} joined</span>
        <span data-field="paid">${paidCount} paid</span>
        <span data-field="pending">${pendingCount} pending deposit</span>
        <span data-field="spots">${escapeHtml(spotsLabel(roster.length || joined, max, lookingFor))}</span>
      </div>
      <div class="coach-req-roster" data-roster>${rosterHtml(roster)}</div>
      ${coachNote ? `<p class="coach-req-card__coach-note">${escapeHtml(coachNote)}</p>` : ''}
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-adjust-details>View &amp; adjust details</button>
      </div>
      <p class="coach-req-card__hint">Deposits are handled by parents/players. You only see who’s confirmed and paid.</p>
    `;

    hosted.querySelector('[data-adjust-details]')?.addEventListener('click', () => openAdjustOverlay(card));
  }

  function markCardHosted(card) {
    card.classList.remove('is-open');
    card.classList.add('is-hosted');

    const status = card.querySelector('.coach-req-status');
    if (status) {
      status.className = 'coach-req-status coach-req-status--hosted';
      status.innerHTML = '<span class="coach-req-pulse coach-req-pulse--green"></span> Open to join';
    }

    card.querySelector('.coach-req-card__timer')?.remove();
    card.querySelectorAll('.coach-req-card__actions').forEach((el) => {
      if (!el.closest('.coach-req-card__hosted')) el.remove();
    });
    const oldHint = [...card.querySelectorAll('.coach-req-card__hint')].find((el) => !el.closest('.coach-req-card__hosted'));
    oldHint?.remove();

    let players = parsePlayers(card);
    if (!players.length) {
      const name = card.querySelector('.coach-req-card__player strong')?.textContent?.trim() || 'Player';
      const initials = card.querySelector('.coach-req-card__player .admin-person-fallback')?.textContent?.trim() || 'PL';
      players = [{
        id: `p-${card.dataset.requestId || Date.now()}`,
        name,
        initials,
        role: 'requester',
        paid: false,
        paid_with: '',
      }];
    }

    const max = card.dataset.maxPlayers ?? '';
    const min = card.dataset.minPlayers ?? '';
    let looking = card.dataset.lookingFor ?? '';
    if (looking === '' && max !== '') looking = String(Math.max(0, Number(max) - players.length));

    renderHostedFooter(card, {
      joined: players.length,
      max,
      min,
      lookingFor: looking,
      coachNote: card.dataset.coachNote || '',
      players,
    });
    updateCountdowns();
  }

  function openAdjustOverlay(card) {
    adjustingCard = card;
    lookingManual = false;
    const players = parsePlayers(card);
    const joined = players.length || Number(card.dataset.playersJoined || 1) || 1;
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
    card.querySelector('[data-accept-request]')?.addEventListener('click', () => {
      const id = card.dataset.requestId;
      const stored = id ? readStoredRequests().find((item) => item.id === id) : null;
      const fallbackName = card.querySelector('.coach-req-card__player strong')?.textContent?.trim() || 'Player';
      const fallbackInitials = card.querySelector('.coach-req-card__player .admin-person-fallback')?.textContent?.trim() || 'PL';
      const storedCard = stored?.card_on_file || '';

      let players = Array.isArray(stored?.players) && stored.players.length
        ? stored.players.map((player) => {
            const isRequester = player.role === 'requester';
            const cardLabel = player.card_on_file || (isRequester ? storedCard : '');
            if (isRequester && cardLabel) {
              return { ...player, paid: true, paid_with: cardLabel, card_on_file: cardLabel };
            }
            return player;
          })
        : [{
            id: `p-${id || Date.now()}`,
            name: fallbackName,
            initials: fallbackInitials,
            role: 'requester',
            paid: !!storedCard,
            paid_with: storedCard,
            card_on_file: storedCard,
          }];

      if (!players.some((p) => p.role === 'requester') && storedCard) {
        players.unshift({
          id: `p-${id || Date.now()}`,
          name: fallbackName,
          initials: fallbackInitials,
          role: 'requester',
          paid: true,
          paid_with: storedCard,
          card_on_file: storedCard,
        });
      }

      setPlayers(card, players);
      card.dataset.playersJoined = String(players.length);
      const max = card.dataset.maxPlayers || '';
      if (max !== '' && (card.dataset.lookingFor === '' || card.dataset.lookingFor == null)) {
        card.dataset.lookingFor = String(Math.max(0, Number(max) - players.length));
      }
      markCardHosted(card);
      if (id) {
        patchStoredRequest(id, {
          status: 'hosted',
          accepted_by: 'You',
          deposit_paid: players.some((p) => p.role === 'requester' && p.paid),
          paid_with: players.find((p) => p.role === 'requester')?.paid_with || storedCard,
          players,
          players_joined: players.length,
          max_players: card.dataset.maxPlayers || '',
          min_players: card.dataset.minPlayers || '',
          looking_for: card.dataset.lookingFor || '',
        });
      }
    });

    card.querySelector('[data-decline-request]')?.addEventListener('click', () => {
      const id = card.dataset.requestId;
      if (id) {
        writeStoredRequests(readStoredRequests().filter((item) => item.id !== id));
      }
      card.remove();
      updateCountdowns();
    });

    card.querySelector('[data-adjust-details]')?.addEventListener('click', () => openAdjustOverlay(card));
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
    card.dataset.acceptSeconds = String(req.accept_seconds || 900);
    card.dataset.countdownStarted = String(Date.now());
    setPlayers(card, players);
    if (req.acceptExpiresAt) card.dataset.acceptExpires = String(req.acceptExpiresAt);

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
        <div class="coach-req-card__grid">
          <div><dt>When</dt><dd>${escapeHtml(req.when || '—')}</dd></div>
          <div><dt>Location</dt><dd>${escapeHtml(req.location || '—')}<span>${escapeHtml(req.city || '')}</span></dd></div>
          <div><dt>Session type</dt><dd data-field="session_type">${escapeHtml(req.session_type || '—')}</dd></div>
          <div><dt>Age range</dt><dd>${escapeHtml(req.age_range || '—')}</dd></div>
          <div><dt>Budget / player</dt><dd>${escapeHtml(req.price_range || '—')}</dd></div>
          ${payoutFieldHtml(req, players.length || Number(card.dataset.playersJoined || 0))}
          <div><dt>Sport</dt><dd>${escapeHtml(req.sport || 'Soccer')}</dd></div>
          ${extraFieldsHtml(req)}
        </div>
        ${req.notes ? `<p class="coach-req-card__notes">${escapeHtml(req.notes)}</p>` : ''}
      `;
      renderHostedFooter(card, {
        joined: players.length || Number(card.dataset.playersJoined || 1),
        max: req.max_players ?? '',
        min: req.min_players ?? '',
        lookingFor: req.looking_for ?? '',
        coachNote: req.coach_note || '',
        players,
      });
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
      <div class="coach-req-card__grid">
        <div><dt>When</dt><dd>${escapeHtml(req.when || '—')}</dd></div>
        <div><dt>Location</dt><dd>${escapeHtml(req.location || '—')}<span>${escapeHtml(req.city || '')}</span></dd></div>
        <div><dt>Session type</dt><dd data-field="session_type">${escapeHtml(req.session_type || '—')}</dd></div>
        <div><dt>Age range</dt><dd>${escapeHtml(req.age_range || '—')}</dd></div>
        <div><dt>Budget / player</dt><dd>${escapeHtml(req.price_range || '—')}</dd></div>
        ${payoutFieldHtml(req, 0)}
        <div><dt>Sport</dt><dd>${escapeHtml(req.sport || 'Soccer')}</dd></div>
        ${extraFieldsHtml(req)}
      </div>
      ${req.notes ? `<p class="coach-req-card__notes">${escapeHtml(req.notes)}</p>` : ''}
      <div class="coach-req-card__timer">
        <span class="coach-req-card__timer-label">Accept by</span>
        <span class="coach-req-countdown" data-countdown>—</span>
      </div>
      <div class="coach-req-card__actions">
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-accept-request>Accept &amp; host</button>
        <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-decline-request>Decline</button>
      </div>
      <p class="coach-req-card__hint">If you accept, the $10 deposit is charged to the parent’s card on file. You just host — and can see who’s paid.</p>
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
      const players = Array.isArray(match.players) ? match.players : parsePlayers(card);
      renderHostedFooter(card, {
        joined: players.length || Number(match.players_joined || 1),
        max: match.max_players ?? card.dataset.maxPlayers ?? '',
        min: match.min_players ?? card.dataset.minPlayers ?? '',
        lookingFor: match.looking_for ?? card.dataset.lookingFor ?? '',
        coachNote: match.coach_note || card.dataset.coachNote || '',
        players,
      });
    });
    updateBadges();
  }

  function loadLiveRequests() {
    const stored = readStoredRequests();
    const staticIds = new Set(
      [...list.querySelectorAll('.coach-req-card')].map((c) => c.dataset.requestId).filter(Boolean)
    );
    const seen = getSeenIds();
    const newIds = [];

    stored.forEach((req) => {
      if (!req?.id || staticIds.has(req.id)) return;
      list.prepend(buildCard(req));
      if ((!req.status || req.status === 'open') && !seen.has(req.id)) newIds.push(req.id);
    });

    if (newIds.length) {
      markSeen(newIds);
      openModal();
      bell?.classList.add('coach-req-bell--pulse');
      window.setTimeout(() => bell?.classList.remove('coach-req-bell--pulse'), 2400);
    }

    refreshHostedFromStorage();
    updateCountdowns();
  }

  adjustJoined?.addEventListener('input', syncLookingHint);
  adjustMax?.addEventListener('input', () => {
    lookingManual = false;
    syncLookingHint();
  });
  adjustLooking?.addEventListener('input', () => {
    lookingManual = true;
  });

  adjustSaveBtn?.addEventListener('click', () => {
    if (!adjustingCard) return;
    const joined = Number(adjustJoined?.value || 0);
    const max = adjustMax?.value === '' ? '' : Number(adjustMax.value);
    const min = adjustMin?.value === '' ? '' : Number(adjustMin.value);
    const looking = adjustLooking?.value === '' ? '' : Number(adjustLooking.value);
    const note = adjustNote?.value.trim() || '';
    const players = parsePlayers(adjustingCard);

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
    if (id) {
      patchStoredRequest(id, {
        status: 'hosted',
        players_joined: joined,
        max_players: max === '' ? '' : max,
        min_players: min === '' ? '' : min,
        looking_for: looking === '' ? '' : looking,
        coach_note: note,
        players,
      });
    }

    renderHostedFooter(adjustingCard, {
      joined,
      max: max === '' ? '' : max,
      min: min === '' ? '' : min,
      lookingFor: looking === '' ? '' : looking,
      coachNote: note,
      players,
    });
    closeAdjustOverlay();
    updateBadges();
  });

  adjustCancelBtn?.addEventListener('click', closeAdjustOverlay);

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
    if (event.key !== 'Escape') return;
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
    refreshHostedFromStorage();
  }, 1500);

  window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY) loadLiveRequests();
  });
})();
