(() => {
  const STORAGE_KEY = 'coachnow_session_requests';
  const DEPOSIT_AMOUNT = 10;

  const state = {
    location: '',
    sport: 'Soccer',
    date: '',
    preferredTime: 'Anytime',
    locationId: '',
    locationName: '',
    locationCity: '',
    selectedDate: null,
    selectedTime: '',
    ageRange: '',
    priceRange: '',
    sessionType: '',
    knowByAt: null,
    minPlayers: '',
    maxPlayers: '',
    playerLevel: '',
    notes: '',
    requestId: '',
  };

  const morningSlots = ['8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM'];
  const afternoonSlots = ['12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM', '6:00 PM', '7:00 PM'];
  const eveningSlots = ['7:30 PM', '8:30 PM', '9:30 PM'];

  const els = {
    progressFill: document.getElementById('reqProgressFill'),
    progressSteps: document.querySelectorAll('#reqProgressSteps li'),
    steps: document.querySelectorAll('.req-step'),
    form1: document.getElementById('reqFormStep1'),
    form4: document.getElementById('reqFormStep4'),
    locationInput: document.getElementById('reqLocation'),
    useLocationBtn: document.getElementById('reqUseLocation'),
    dateInput: document.getElementById('reqDate'),
    locationCards: document.querySelectorAll('.req-loc-card'),
    locationLabel: document.getElementById('reqSelectedLocationLabel'),
    dateStrip: document.getElementById('reqDateStrip'),
    morningSlots: document.getElementById('reqMorningSlots'),
    afternoonSlots: document.getElementById('reqAfternoonSlots'),
    eveningSlots: document.getElementById('reqEveningSlots'),
    toDetailsBtn: document.getElementById('reqToDetailsBtn'),
    summary: document.getElementById('reqSummary'),
    liveSummary: document.getElementById('reqLiveSummary'),
    requestId: document.getElementById('reqRequestId'),
    countdownDisplay: document.getElementById('reqCountdownDisplay'),
    countdownHint: document.getElementById('reqCountdownHint'),
    startAnother: document.getElementById('reqStartAnother'),
    knowByInput: document.getElementById('reqKnowBy'),
    depositPanel: document.getElementById('reqDepositPanel'),
    depositPaid: document.getElementById('reqDepositPaid'),
    payDepositBtn: document.getElementById('reqPayDepositBtn'),
  };

  let countdownTimer = null;

  function setMinDate() {
    if (!els.dateInput) return;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    els.dateInput.min = toISODate(today);
  }

  function toISODate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function toDateTimeLocal(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${y}-${m}-${d}T${h}:${min}`;
  }

  function formatDisplayDate(date) {
    return date.toLocaleDateString(undefined, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  }

  function formatKnowBy(date) {
    return date.toLocaleString(undefined, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  }

  function formatShortDate(date) {
    return date.toLocaleDateString(undefined, { weekday: 'short' });
  }

  function parseTimeLabel(label) {
    const match = String(label).match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return { hours: 12, minutes: 0 };
    let hours = Number(match[1]);
    const minutes = Number(match[2]);
    const period = match[3].toUpperCase();
    if (period === 'PM' && hours < 12) hours += 12;
    if (period === 'AM' && hours === 12) hours = 0;
    return { hours, minutes };
  }

  function sessionDateTime() {
    if (!state.selectedDate) return null;
    const { hours, minutes } = parseTimeLabel(state.selectedTime || '12:00 PM');
    const d = new Date(state.selectedDate);
    d.setHours(hours, minutes, 0, 0);
    return d;
  }

  function goToStep(step) {
    els.steps.forEach((section) => {
      const n = Number(section.dataset.step);
      const active = n === step;
      section.hidden = !active;
      section.classList.toggle('is-active', active);
    });

    const pct = Math.min(step, 5) === 5 ? 100 : (Math.min(step, 4) / 4) * 100;
    if (els.progressFill) els.progressFill.style.width = `${pct}%`;

    els.progressSteps.forEach((item) => {
      const n = Number(item.dataset.step);
      item.classList.toggle('is-active', n === Math.min(step, 4));
      item.classList.toggle('is-done', n < step);
    });

    scrollToStage();
  }

  function scrollToStage() {
    const anchor = document.querySelector('.req-body');
    if (!anchor) return;

    const header = document.getElementById('siteHeader');
    const offset = (header ? header.offsetHeight : 0) + 16;

    if (window.coachNowLenis) {
      window.coachNowLenis.scrollTo(anchor, { offset: -offset });
      return;
    }

    const top = anchor.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  }

  function renderTimeSlots(container, slots) {
    if (!container) return;
    container.innerHTML = '';
    slots.forEach((label) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'req-time-slot';
      btn.textContent = label;
      btn.dataset.time = label;
      btn.addEventListener('click', () => selectTime(label, btn));
      container.appendChild(btn);
    });
  }

  function selectTime(label, btn) {
    state.selectedTime = label;
    document.querySelectorAll('.req-time-slot').forEach((el) => {
      el.classList.toggle('is-active', el === btn);
    });
    if (els.toDetailsBtn) els.toDetailsBtn.disabled = false;
    updateSummary();
  }

  function buildDateStrip(baseDate) {
    if (!els.dateStrip) return;
    els.dateStrip.innerHTML = '';
    const start = new Date(baseDate);
    start.setHours(0, 0, 0, 0);

    for (let i = 0; i < 7; i += 1) {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'req-date-btn';
      btn.innerHTML = `<span>${formatShortDate(d)}</span><strong>${d.getDate()}</strong>`;
      btn.addEventListener('click', () => {
        state.selectedDate = d;
        els.dateStrip.querySelectorAll('.req-date-btn').forEach((b) => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        updateSummary();
      });
      if (i === 0) {
        state.selectedDate = d;
        btn.classList.add('is-active');
      }
      els.dateStrip.appendChild(btn);
    }
  }

  function filterTimeGroups() {
    const pref = state.preferredTime;
    document.querySelectorAll('.req-time-group').forEach((group) => {
      const period = group.dataset.period;
      if (pref === 'Anytime') {
        group.hidden = false;
        return;
      }
      group.hidden = period !== pref;
    });
  }

  function updateSummary() {
    if (!els.summary) return;
    const dateStr = state.selectedDate ? formatDisplayDate(state.selectedDate) : '—';
    els.summary.innerHTML = `
      <dl>
        <div><dt>Location</dt><dd>${escapeHtml(state.locationName || state.location || '—')}</dd></div>
        <div><dt>Sport</dt><dd>${escapeHtml(state.sport)}</dd></div>
        <div><dt>Date</dt><dd>${escapeHtml(dateStr)}</dd></div>
        <div><dt>Time</dt><dd>${escapeHtml(state.selectedTime || '—')}</dd></div>
      </dl>
    `;
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatRemaining(seconds) {
    const s = Math.max(0, Math.floor(seconds));
    const d = Math.floor(s / 86400);
    const h = Math.floor((s % 86400) / 3600);
    const m = Math.floor((s % 3600) / 60);
    const r = s % 60;
    if (d > 0) return `${d}d ${h}h`;
    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}m`;
    return `${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
  }

  function startCutoffCountdown(expiresAt) {
    if (countdownTimer) clearInterval(countdownTimer);

    const tick = () => {
      const remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
      if (els.countdownDisplay) els.countdownDisplay.textContent = formatRemaining(remaining);
      if (remaining <= 0) {
        clearInterval(countdownTimer);
        if (els.countdownDisplay) els.countdownDisplay.textContent = '00:00';
        if (els.countdownHint) els.countdownHint.textContent = 'Cutoff passed — this request is no longer open for coaches';
      }
    };

    tick();
    countdownTimer = setInterval(tick, 1000);
  }

  function playersLabel() {
    if (state.minPlayers && state.maxPlayers) return `${state.minPlayers}–${state.maxPlayers}`;
    if (state.minPlayers) return `${state.minPlayers}+`;
    if (state.maxPlayers) return `Up to ${state.maxPlayers}`;
    return '';
  }

  function renderLiveSummary() {
    if (!els.liveSummary) return;
    const dateStr = state.selectedDate ? formatDisplayDate(state.selectedDate) : state.date;
    const extra = [];
    extra.push(`<div><dt>Location</dt><dd>${escapeHtml(state.locationName)}</dd></div>`);
    extra.push(`<div><dt>Date</dt><dd>${escapeHtml(dateStr)}</dd></div>`);
    extra.push(`<div><dt>Time</dt><dd>${escapeHtml(state.selectedTime)}</dd></div>`);
    extra.push(`<div><dt>Session</dt><dd>${escapeHtml(state.sessionType)}</dd></div>`);
    extra.push(`<div><dt>Age range</dt><dd>${escapeHtml(state.ageRange)}</dd></div>`);
    extra.push(`<div><dt>Price / player</dt><dd>${escapeHtml(state.priceRange)}</dd></div>`);
    if (playersLabel()) extra.push(`<div><dt>Players</dt><dd>${escapeHtml(playersLabel())}</dd></div>`);
    if (state.playerLevel) extra.push(`<div><dt>Level</dt><dd>${escapeHtml(state.playerLevel)}</dd></div>`);
    extra.push(`<div><dt>Deposit</dt><dd>$${DEPOSIT_AMOUNT} after a coach accepts</dd></div>`);
    els.liveSummary.innerHTML = extra.join('');
  }

  function cutoffFromPreset(kind) {
    const now = new Date();
    if (kind === '2h') return new Date(now.getTime() + 2 * 60 * 60 * 1000);
    if (kind === 'tonight') {
      const d = new Date();
      d.setHours(20, 0, 0, 0);
      if (d <= now) d.setDate(d.getDate() + 1);
      return d;
    }
    if (kind === 'tomorrow') {
      const d = new Date();
      d.setDate(d.getDate() + 1);
      d.setHours(10, 0, 0, 0);
      return d;
    }
    return now;
  }

  function syncKnowByBounds() {
    if (!els.knowByInput) return;
    const now = new Date();
    els.knowByInput.min = toDateTimeLocal(now);
    const session = sessionDateTime();
    if (session) {
      const max = new Date(session.getTime() - 30 * 60 * 1000);
      if (max > now) els.knowByInput.max = toDateTimeLocal(max);
      else els.knowByInput.removeAttribute('max');
    }
  }

  function applyKnowBy(date, preset) {
    state.knowByAt = date;
    if (els.knowByInput) els.knowByInput.value = toDateTimeLocal(date);
    document.querySelectorAll('#reqKnowByPresets .req-chip').forEach((chip) => {
      chip.classList.toggle('is-active', chip.dataset.knowBy === preset);
    });
  }

  function readStoredRequests() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
      return [];
    }
  }

  function writeStoredRequests(list) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, 20)));
  }

  function updateStoredRequest(id, patch) {
    const list = readStoredRequests();
    const idx = list.findIndex((item) => item.id === id);
    if (idx === -1) return;
    list[idx] = { ...list[idx], ...patch };
    writeStoredRequests(list);
  }

  function showDepositState(status) {
    const awaiting = status === 'awaiting_deposit';
    const confirmed = status === 'confirmed' || status === 'hosted';
    if (els.depositPanel) els.depositPanel.hidden = !awaiting;
    if (els.depositPaid) els.depositPaid.hidden = !confirmed;
    if (awaiting) {
      window.CoachNowPayment?.mount(document.querySelector('#reqDepositPanel [data-payment-root]'));
    }
  }

  function syncLiveRequestStatus() {
    if (!state.requestId) return;
    const match = readStoredRequests().find((item) => item.id === state.requestId);
    if (!match) return;
    showDepositState(match.status);
  }

  function resetFlow() {
    if (countdownTimer) clearInterval(countdownTimer);
    state.selectedTime = '';
    state.selectedDate = null;
    state.knowByAt = null;
    state.requestId = '';
    state.minPlayers = '';
    state.maxPlayers = '';
    state.playerLevel = '';
    if (els.form1) els.form1.reset();
    if (els.form4) els.form4.reset();
    if (els.toDetailsBtn) els.toDetailsBtn.disabled = true;
    showDepositState('');
    document.querySelectorAll('#reqKnowByPresets .req-chip').forEach((chip) => chip.classList.remove('is-active'));
    setMinDate();
    goToStep(1);
  }

  if (els.form1) {
    els.form1.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!els.form1.checkValidity()) {
        els.form1.reportValidity();
        return;
      }
      state.location = els.locationInput?.value.trim() || '';
      state.sport = document.getElementById('reqSport')?.value || 'Soccer';
      state.date = els.dateInput?.value || '';
      state.preferredTime = document.getElementById('reqPreferredTime')?.value || 'Anytime';

      const base = state.date ? new Date(`${state.date}T12:00:00`) : new Date();
      buildDateStrip(base);
      filterTimeGroups();
      goToStep(2);
    });
  }

  if (els.useLocationBtn) {
    els.useLocationBtn.addEventListener('click', () => {
      if (els.locationInput) els.locationInput.value = 'Sommers Bend, Murrieta, CA';
    });
  }

  els.locationCards.forEach((card) => {
    const select = () => {
      state.locationId = card.dataset.locationId || '';
      state.locationName = card.dataset.locationName || '';
      state.locationCity = card.dataset.locationCity || '';
      if (els.locationLabel) els.locationLabel.textContent = state.locationName;
      els.locationCards.forEach((c) => c.classList.toggle('is-selected', c === card));
      renderTimeSlots(els.morningSlots, morningSlots);
      renderTimeSlots(els.afternoonSlots, afternoonSlots);
      renderTimeSlots(els.eveningSlots, eveningSlots);
      if (els.toDetailsBtn) els.toDetailsBtn.disabled = true;
      state.selectedTime = '';
      updateSummary();
      goToStep(3);
    };
    card.addEventListener('click', select);
    card.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      select();
    });
    card.querySelector('.req-loc-card__cta')?.addEventListener('click', (e) => {
      e.stopPropagation();
      select();
    });
  });

  if (els.toDetailsBtn) {
    els.toDetailsBtn.addEventListener('click', () => {
      if (!state.selectedTime) return;
      updateSummary();
      syncKnowByBounds();
      goToStep(4);
    });
  }

  document.querySelectorAll('#reqKnowByPresets .req-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      applyKnowBy(cutoffFromPreset(chip.dataset.knowBy), chip.dataset.knowBy);
    });
  });

  els.knowByInput?.addEventListener('change', () => {
    document.querySelectorAll('#reqKnowByPresets .req-chip').forEach((chip) => chip.classList.remove('is-active'));
    if (els.knowByInput.value) state.knowByAt = new Date(els.knowByInput.value);
  });

  if (els.form4) {
    ['reqAgeRange', 'reqPriceRange', 'reqSessionType'].forEach((id) => {
      document.getElementById(id)?.addEventListener('change', updateSummary);
    });

    els.form4.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!els.form4.checkValidity()) {
        els.form4.reportValidity();
        return;
      }

      const minPlayers = document.getElementById('reqMinPlayers')?.value || '';
      const maxPlayers = document.getElementById('reqMaxPlayers')?.value || '';
      if (minPlayers && maxPlayers && Number(minPlayers) > Number(maxPlayers)) {
        document.getElementById('reqMaxPlayers')?.setCustomValidity('Max players must be greater than or equal to min players.');
        els.form4.reportValidity();
        document.getElementById('reqMaxPlayers')?.setCustomValidity('');
        return;
      }

      const knowByValue = els.knowByInput?.value;
      if (!knowByValue) {
        els.knowByInput?.reportValidity();
        return;
      }

      const knowByAt = new Date(knowByValue);
      const now = Date.now();
      const session = sessionDateTime();
      if (knowByAt.getTime() <= now) {
        els.knowByInput?.setCustomValidity('Choose a cutoff in the future.');
        els.form4.reportValidity();
        els.knowByInput?.setCustomValidity('');
        return;
      }
      if (session && knowByAt.getTime() >= session.getTime()) {
        els.knowByInput?.setCustomValidity('Need-to-know-by must be before the session starts.');
        els.form4.reportValidity();
        els.knowByInput?.setCustomValidity('');
        return;
      }

      state.ageRange = document.getElementById('reqAgeRange')?.value || '';
      state.priceRange = document.getElementById('reqPriceRange')?.value || '';
      state.sessionType = document.getElementById('reqSessionType')?.value || '';
      state.knowByAt = knowByAt;
      state.minPlayers = minPlayers;
      state.maxPlayers = maxPlayers;
      state.playerLevel = document.getElementById('reqPlayerLevel')?.value || '';
      state.notes = document.getElementById('reqNotes')?.value.trim() || '';

      const idNum = Math.floor(1000 + Math.random() * 9000);
      const requestId = `CN-${idNum}`;
      state.requestId = requestId;
      if (els.requestId) els.requestId.textContent = `#${requestId}`;

      saveSessionRequest(requestId);
      renderLiveSummary();
      if (els.countdownHint) {
        els.countdownHint.textContent = `Coaches can accept until ${formatKnowBy(knowByAt)}`;
      }
      startCutoffCountdown(knowByAt.getTime());
      showDepositState('open');
      goToStep(5);
    });
  }

  function saveSessionRequest(requestId) {
    const dateStr = state.selectedDate
      ? state.selectedDate.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
      : state.date;
    const when = [dateStr, state.selectedTime].filter(Boolean).join(' · ');
    const expiresAt = state.knowByAt ? state.knowByAt.getTime() : Date.now();

    const payload = {
      id: requestId,
      initials: 'NR',
      name: 'New session request',
      location: state.locationName || state.location,
      city: state.locationCity || '',
      when,
      session_type: state.sessionType,
      age_range: state.ageRange,
      price_range: state.priceRange,
      sport: state.sport,
      notes: state.notes,
      min_players: state.minPlayers ? Number(state.minPlayers) : '',
      max_players: state.maxPlayers ? Number(state.maxPlayers) : '',
      player_level: state.playerLevel,
      know_by: state.knowByAt ? formatKnowBy(state.knowByAt) : '',
      deposit: DEPOSIT_AMOUNT,
      status: 'open',
      accept_seconds: Math.max(1, Math.floor((expiresAt - Date.now()) / 1000)),
      posted: 'Just now',
      createdAt: Date.now(),
      acceptExpiresAt: expiresAt,
    };

    try {
      const existing = readStoredRequests();
      existing.unshift(payload);
      writeStoredRequests(existing);
    } catch {
      /* ignore storage errors in demo mode */
    }
  }

  els.payDepositBtn?.addEventListener('click', () => {
    if (!state.requestId) return;
    const wallet = window.CoachNowPayment?.api(document.querySelector('#reqDepositPanel [data-payment-root]'));
    const result = wallet?.charge();
    if (!result?.ok) return;
    updateStoredRequest(state.requestId, {
      status: 'hosted',
      deposit_paid: true,
      paid_with: `${result.method.brand} ···· ${result.method.last4}`,
      accepted_by: 'You',
      players_joined: 1,
    });
    const paidWith = document.getElementById('reqPaidWith');
    if (paidWith) paidWith.textContent = `Paid with ${result.method.brand} ···· ${result.method.last4} · Session stays open for others to join`;
    showDepositState('hosted');
  });

  document.querySelectorAll('[data-go-step]').forEach((btn) => {
    btn.addEventListener('click', () => {
      goToStep(Number(btn.dataset.goStep));
    });
  });

  if (els.startAnother) {
    els.startAnother.addEventListener('click', resetFlow);
  }

  window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY) syncLiveRequestStatus();
  });
  setInterval(syncLiveRequestStatus, 1500);

  renderTimeSlots(els.morningSlots, morningSlots);
  renderTimeSlots(els.afternoonSlots, afternoonSlots);
  renderTimeSlots(els.eveningSlots, eveningSlots);
  setMinDate();

  els.steps.forEach((section) => {
    const active = Number(section.dataset.step) === 1;
    section.hidden = !active;
    section.classList.toggle('is-active', active);
  });
})();
