(() => {
  const DEPOSIT_AMOUNT = 10;

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
    if (response.status === 401) {
      window.location.href = '/login';
      throw new Error('Unauthorized');
    }
    if (!response.ok) {
      const message = payload.message || payload.errors && Object.values(payload.errors).flat()[0] || 'Request failed';
      throw new Error(message);
    }
    return payload;
  }

  const params = new URLSearchParams(window.location.search);
  const coachFromPage = document.getElementById('reqRequestedCoachId')?.value || '';
  const coachFromQuery = params.get('coach') || '';

  const state = {
    location: '',
    sport: '',
    date: '',
    preferredTime: '',
    locationId: '',
    locationName: '',
    locationCity: '',
    selectedDate: null,
    selectedTime: '',
    ageRange: '',
    priceRange: '',
    sessionType: '',
    knowByAt: null,
    requestedCoachId: String(coachFromPage || coachFromQuery || ''),
    preferredLocationId: document.getElementById('reqStage')?.dataset.preferredLocation || '',
    sortByNearest: false,
    minPlayers: '',
    maxPlayers: '',
    playerLevel: '',
    notes: '',
    requestId: '',
    cardOnFile: '',
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
    useCoachParkBtn: document.getElementById('reqUseCoachPark'),
    locationRefine: document.getElementById('reqLocationRefine'),
    showAllLocationsBtn: document.getElementById('reqShowAllLocations'),
    locationEmpty: document.getElementById('reqLocationEmpty'),
    locationEmptyClear: document.getElementById('reqLocationEmptyClear'),
    locationCount: document.getElementById('reqLocationCount'),
    locationStepLead: document.getElementById('reqLocationStepLead'),
    locationList: document.getElementById('reqLocations'),
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
    waitingCoach: document.getElementById('reqWaitingCoach'),
    depositPaid: document.getElementById('reqDepositPaid'),
    joinDepositPanel: document.getElementById('reqJoinDepositPanel'),
    joinAsAnother: document.getElementById('reqJoinAsAnother'),
    joinPayBtn: document.getElementById('reqJoinPayBtn'),
    joinCancelBtn: document.getElementById('reqJoinCancelBtn'),
    cardModal: document.getElementById('reqCardModal'),
    cardConfirmBtn: document.getElementById('reqCardConfirmBtn'),
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

  function notify(message, title = 'Notice') {
    if (window.CoachNowDialog?.alert) {
      return window.CoachNowDialog.alert({ title, message, confirmLabel: 'Got it' });
    }
    window.alert(message);
    return Promise.resolve(true);
  }

  function filterLocations(query, { sortNearest = false } = {}) {
    const q = String(query || '').trim().toLowerCase();
    const preferred = state.preferredLocationId;
    const cards = [...els.locationCards];
    let visible = 0;

    cards.forEach((card) => {
      const name = (card.dataset.locationName || '').toLowerCase();
      const city = (card.dataset.locationCity || '').toLowerCase();
      const id = card.dataset.locationId || '';
      const hay = `${name} ${city} ${id}`;
      const isPreferred = preferred && id === preferred;
      const matches = !q || hay.includes(q);
      card.hidden = !matches;
      card.classList.toggle('is-recommended', Boolean(isPreferred && matches));
      const badge = card.querySelector('.req-loc-card__badge');
      if (badge) badge.hidden = !(isPreferred && matches);
      if (matches) visible += 1;
    });

    if (els.locationList) {
      const sorted = cards.slice().sort((a, b) => {
        const aPref = preferred && a.dataset.locationId === preferred ? 0 : 1;
        const bPref = preferred && b.dataset.locationId === preferred ? 0 : 1;
        if (aPref !== bPref) return aPref - bPref;
        if (sortNearest || state.sortByNearest) {
          return Number(a.dataset.distance || 999) - Number(b.dataset.distance || 999);
        }
        return 0;
      });
      sorted.forEach((card) => els.locationList.appendChild(card));
    }

    if (els.locationCount) {
      els.locationCount.hidden = false;
      els.locationCount.textContent = visible === 1
        ? '1 park shown'
        : `${visible} parks shown`;
    }

    if (els.locationEmpty) {
      els.locationEmpty.hidden = visible !== 0;
    }

    if (visible === 0 && q) {
      cards.forEach((card) => {
        card.hidden = false;
        visible += 1;
      });
      if (els.locationEmpty) els.locationEmpty.hidden = true;
      if (els.locationCount) {
        els.locationCount.textContent = `${visible} parks shown — no exact match, showing all`;
      }
      if (els.locationStepLead) {
        els.locationStepLead.textContent = `No parks matched “${query.trim()}”. Showing all parks so you can pick one.`;
      }
      return visible;
    }

    if (els.locationStepLead) {
      if (q) {
        els.locationStepLead.textContent = `Showing parks matching “${query.trim()}”. Pick one to continue.`;
      } else if (sortNearest || state.sortByNearest) {
        els.locationStepLead.textContent = 'Parks sorted by nearest distance. Pick one to continue.';
      } else if (preferred) {
        els.locationStepLead.textContent = 'Recommended park is marked — or pick another location.';
      } else {
        els.locationStepLead.textContent = 'Pick a park to continue.';
      }
    }

    return visible;
  }

  function syncLocationRefine(value) {
    if (els.locationRefine) els.locationRefine.value = value || '';
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
    const pref = state.preferredTime || 'Anytime';
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

  function selectedCoachMeta() {
    const el = document.getElementById('reqRequestedCoachId');
    if (!el) return {};
    if (el.tagName === 'SELECT') {
      const opt = el.selectedOptions?.[0];
      return {
        rate: Number(opt?.dataset.coachRate || 0),
        ages: opt?.dataset.coachAges || '',
        specialty: opt?.dataset.coachSpecialty || '',
        parkId: opt?.dataset.parkId || '',
      };
    }
    return {
      rate: Number(el.dataset.coachRate || 0),
      ages: el.dataset.coachAges || '',
      specialty: el.dataset.coachSpecialty || '',
      parkId: document.getElementById('reqStage')?.dataset.preferredLocation || '',
    };
  }

  function budgetFromRate(rate) {
    if (!rate) return '';
    if (rate <= 25) return 'Up to $25 / player';
    if (rate <= 50) return '$25 – $50 / player';
    if (rate <= 100) return '$50 – $100 / player';
    if (rate <= 150) return '$100 – $150 / player';
    return '$150+ / player';
  }

  function ageFromCoachAges(ages) {
    const map = {
      'Ages 5-10': 'U10 (9–10 years)',
      'Ages 6-12': 'U12 (11–12 years)',
      'Ages 8-14': 'U14 (13–14 years)',
      'Ages 10-16': 'U16 (15–16 years)',
      'Ages 12-18': 'U18 (17–18 years)',
    };
    if (!ages || ages === 'All ages') return '';
    return map[ages] || '';
  }

  function applyDetailsFromPreviousSteps() {
    const meta = selectedCoachMeta();
    const ageEl = document.getElementById('reqAgeRange');
    const priceEl = document.getElementById('reqPriceRange');
    const typeEl = document.getElementById('reqSessionType');

    if (ageEl && !ageEl.value) {
      const mapped = ageFromCoachAges(meta.ages);
      if (mapped) ageEl.value = mapped;
    }
    if (priceEl && !priceEl.value) {
      const mapped = budgetFromRate(meta.rate);
      if (mapped) priceEl.value = mapped;
    }
    if (typeEl && !typeEl.value && /1-on-1|private/i.test(meta.specialty || '')) {
      typeEl.value = 'Private 1-on-1 Session';
    }
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
    extra.push(`<div><dt>Budget / player</dt><dd>${escapeHtml(state.priceRange)}</dd></div>`);
    if (playersLabel()) extra.push(`<div><dt>Players</dt><dd>${escapeHtml(playersLabel())}</dd></div>`);
    if (state.playerLevel) extra.push(`<div><dt>Level</dt><dd>${escapeHtml(state.playerLevel)}</dd></div>`);
    extra.push(`<div><dt>Deposit</dt><dd>Card on file · $${DEPOSIT_AMOUNT} charged when a coach accepts</dd></div>`);
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
    return [];
  }

  function writeStoredRequests() {}

  function updateStoredRequest() {}

  function showDepositState(status, match) {
    const hosted = status === 'hosted' || status === 'accepted' || status === 'awaiting_deposit' || status === 'confirmed';
    const players = Array.isArray(match?.players) ? match.players : [];
    const requester = players.find((p) => p.role === 'requester') || players[0];
    const requesterPaid = !!(requester?.paid || match?.deposit_paid);
    const joining = els.joinDepositPanel && !els.joinDepositPanel.hidden;
    const cardLabel = match?.card_on_file || requester?.card_on_file || state.cardOnFile || '';

    if (els.waitingCoach) els.waitingCoach.hidden = hosted || joining;
    if (els.depositPaid) els.depositPaid.hidden = !(hosted && requesterPaid) || joining;

    const cardNote = document.getElementById('reqCardOnFileNote');
    if (cardNote && cardLabel && !hosted) {
      cardNote.textContent = `Saved ${cardLabel}. $10 charges automatically when a coach accepts. Keep this tab open, then Accept & host on the coach dashboard.`;
    }

    if (!hosted) {
      const liveStatus = document.querySelector('.req-live-card__status');
      if (liveStatus) liveStatus.innerHTML = '<span class="req-pulse"></span> Open · Card on file';
    }

    if (hosted && requesterPaid) {
      const liveStatus = document.querySelector('.req-live-card__status');
      if (liveStatus) liveStatus.innerHTML = '<span class="req-pulse"></span> Hosted · Deposit charged';
      if (els.countdownHint) els.countdownHint.textContent = 'Coach accepted — $10 charged · session open for others to join';
      const paidLabel = document.getElementById('reqPaidWith');
      if (paidLabel && (requester?.paid_with || cardLabel)) {
        paidLabel.textContent = `Charged to ${requester?.paid_with || cardLabel}`;
      }
    }
  }

  function sessionDateIso() {
    if (state.selectedDate instanceof Date && !Number.isNaN(state.selectedDate.getTime())) {
      const y = state.selectedDate.getFullYear();
      const m = String(state.selectedDate.getMonth() + 1).padStart(2, '0');
      const d = String(state.selectedDate.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    }
    return state.date || '';
  }

  function syncLiveRequestStatus() {
    if (!state.requestId) return;
    apiFetch(`/api/session-requests/${encodeURIComponent(state.requestId)}`)
      .then((payload) => {
        const match = payload.data;
        if (!match) return;
        showDepositState(match.status, match);
      })
      .catch(() => {});
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
    state.cardOnFile = '';
    closeCardModal();
    if (els.form1) els.form1.reset();
    if (els.form4) els.form4.reset();
    if (els.toDetailsBtn) els.toDetailsBtn.disabled = true;
    showDepositState('', null);
    if (els.waitingCoach) els.waitingCoach.hidden = false;
    if (els.joinDepositPanel) els.joinDepositPanel.hidden = true;
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
      state.sport = document.getElementById('reqSport')?.value || '';
      state.date = els.dateInput?.value || '';
      state.preferredTime = document.getElementById('reqPreferredTime')?.value || '';

      if (!state.sport || !state.date || !state.preferredTime) {
        notify('Choose a sport, date, and preferred time before continuing.', 'Missing details');
        return;
      }

      const coachEl = document.getElementById('reqRequestedCoachId');
      const selectedCoachId = coachEl?.value || '';
      state.requestedCoachId = String(selectedCoachId || '');
      const coachMeta = selectedCoachMeta();
      if (coachMeta.parkId) state.preferredLocationId = coachMeta.parkId;

      const base = state.date ? new Date(`${state.date}T12:00:00`) : new Date();
      buildDateStrip(base);
      filterTimeGroups();
      syncLocationRefine(state.location);
      filterLocations(state.location, { sortNearest: state.sortByNearest });
      goToStep(2);
    });
  }

  if (els.useCoachParkBtn) {
    els.useCoachParkBtn.addEventListener('click', () => {
      const name = els.useCoachParkBtn.dataset.parkName || '';
      if (els.locationInput) els.locationInput.value = name;
      state.location = name;
      state.sortByNearest = false;
      els.locationInput?.focus();
    });
  }

  if (els.useLocationBtn) {
    els.useLocationBtn.addEventListener('click', () => {
      state.sortByNearest = true;
      if (els.locationInput) els.locationInput.value = '';
      state.location = '';
      els.useLocationBtn.classList.add('is-active');
    });
  }

  els.locationRefine?.addEventListener('input', () => {
    filterLocations(els.locationRefine.value, { sortNearest: state.sortByNearest });
  });

  const showAllParks = () => {
    state.sortByNearest = false;
    if (els.locationInput) els.locationInput.value = '';
    syncLocationRefine('');
    filterLocations('', { sortNearest: false });
  };

  els.showAllLocationsBtn?.addEventListener('click', showAllParks);
  els.locationEmptyClear?.addEventListener('click', showAllParks);

  els.locationCards.forEach((card) => {
    const select = () => {
      state.locationId = card.dataset.locationId || '';
      state.locationName = card.dataset.locationName || '';
      state.locationCity = card.dataset.locationCity || '';
      state.location = state.locationName;
      if (els.locationInput) els.locationInput.value = state.locationName;
      if (els.locationLabel) els.locationLabel.textContent = state.locationName;
      els.locationCards.forEach((c) => c.classList.toggle('is-selected', c === card));
      renderTimeSlots(els.morningSlots, morningSlots);
      renderTimeSlots(els.afternoonSlots, afternoonSlots);
      renderTimeSlots(els.eveningSlots, eveningSlots);
      filterTimeGroups();
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
      applyDetailsFromPreviousSteps();
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

      openCardModal();
    });
  }

  function openCardModal() {
    if (!els.cardModal) return;
    if (els.cardModal.parentElement !== document.body) {
      document.body.appendChild(els.cardModal);
    }
    els.cardModal.hidden = false;
    document.body.classList.add('req-pay-modal-open');
    window.coachNowLenis?.stop();

    const root = document.querySelector('#reqCardOnFileBox [data-payment-root]');
    const wallet = window.CoachNowPayment?.api(root);
    wallet?.collapseForm?.();
    const body = document.getElementById('reqCardOnFileBox');
    if (body) body.scrollTop = 0;
  }

  function closeCardModal() {
    if (!els.cardModal) return;
    els.cardModal.hidden = true;
    document.body.classList.remove('req-pay-modal-open');
    window.coachNowLenis?.start();
  }

  async function publishRequest(cardLabel) {
    state.cardOnFile = cardLabel;
    // Re-read in case the hidden field was rendered after init.
    const liveCoachId = document.getElementById('reqRequestedCoachId')?.value
      || new URLSearchParams(window.location.search).get('coach')
      || state.requestedCoachId
      || '';
    state.requestedCoachId = String(liveCoachId || '');

    window.CoachNowBusy?.setBusy(els.cardConfirmBtn, { label: 'Publishing…' });
    try {
      const payload = await apiFetch('/api/session-requests', {
        method: 'POST',
        body: JSON.stringify({
          location_id: state.locationId || null,
          location_name: state.locationName || state.location,
          location_city: state.locationCity || '',
          session_date: sessionDateIso(),
          session_time: state.selectedTime || null,
          session_type: state.sessionType,
          sport: state.sport || 'Soccer',
          age_range: state.ageRange || null,
          price_range: state.priceRange || null,
          player_level: state.playerLevel || null,
          notes: state.notes || null,
          min_players: state.minPlayers ? Number(state.minPlayers) : null,
          max_players: state.maxPlayers ? Number(state.maxPlayers) : null,
          know_by_at: state.knowByAt ? state.knowByAt.toISOString() : null,
          card_on_file: cardLabel,
          deposit: DEPOSIT_AMOUNT,
          requested_coach_id: state.requestedCoachId ? Number(state.requestedCoachId) : null,
        }),
      });

      const request = payload.data;
      state.requestId = request.id;
      if (els.requestId) els.requestId.textContent = `#${request.id}`;

      renderLiveSummary();
      const successLead = document.getElementById('reqSuccessLead');
      if (successLead) {
        successLead.textContent = request.requested_coach
          ? `${request.requested_coach} has been notified. You’ll get an update as soon as they accept.`
          : 'Nearby coaches have been notified. The first coach to accept hosts this session.';
      }
      if (els.countdownHint && state.knowByAt) {
        els.countdownHint.textContent = request.requested_coach
          ? `${request.requested_coach} can accept until ${formatKnowBy(state.knowByAt)}`
          : `Coaches can accept until ${formatKnowBy(state.knowByAt)}`;
        startCutoffCountdown(state.knowByAt.getTime());
      }
      showDepositState('open', request);
      if (els.waitingCoach) els.waitingCoach.hidden = false;
      closeCardModal();
      goToStep(5);
    } catch (error) {
      await notify(error.message || 'Could not publish session request.', 'Request failed');
    } finally {
      window.CoachNowBusy?.clearBusy(els.cardConfirmBtn);
    }
  }

  function saveSessionRequest() {}

  els.cardConfirmBtn?.addEventListener('click', () => {
    const wallet = window.CoachNowPayment?.api(document.querySelector('#reqCardOnFileBox [data-payment-root]'));
    const authorized = wallet?.authorize();
    if (!authorized?.ok) return;
    publishRequest(`${authorized.method.brand} ···· ${authorized.method.last4}`);
  });

  document.querySelectorAll('[data-close-card-modal]').forEach((btn) => {
    btn.addEventListener('click', closeCardModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && els.cardModal && !els.cardModal.hidden) closeCardModal();
  });

  els.joinAsAnother?.addEventListener('click', () => {
    if (els.depositPaid) els.depositPaid.hidden = true;
    if (els.waitingCoach) els.waitingCoach.hidden = true;
    if (els.joinDepositPanel) {
      els.joinDepositPanel.hidden = false;
      window.CoachNowPayment?.mount(document.querySelector('#reqJoinDepositPanel [data-payment-root]'));
    }
  });

  els.joinCancelBtn?.addEventListener('click', () => {
    if (els.joinDepositPanel) els.joinDepositPanel.hidden = true;
    syncLiveRequestStatus();
  });

  els.joinPayBtn?.addEventListener('click', async () => {
    if (!state.requestId) return;
    const wallet = window.CoachNowPayment?.api(document.querySelector('#reqJoinDepositPanel [data-payment-root]'));
    const result = wallet?.charge();
    if (!result?.ok) return;

    const paidWith = `${result.method.brand} ···· ${result.method.last4}`;
    window.CoachNowBusy?.setBusy(els.joinPayBtn, { label: 'Joining…' });
    try {
      const payload = await apiFetch(`/api/session-requests/${encodeURIComponent(state.requestId)}/join`, {
        method: 'POST',
        body: JSON.stringify({
          paid: true,
          paid_with: paidWith,
          card_on_file: paidWith,
        }),
      });
      const match = payload.data || {};
      const players = Array.isArray(match.players) ? match.players : [];
      if (els.joinDepositPanel) els.joinDepositPanel.hidden = true;
      showDepositState(match.status || 'hosted', { ...match, deposit_paid: true });
      const paidLabel = document.getElementById('reqPaidWith');
      if (paidLabel) {
        paidLabel.textContent = `${players.length || 1} players on roster · latest join paid with ${paidWith}`;
      }
    } catch (error) {
      await notify(error.message || 'Could not join this session.', 'Join failed');
    } finally {
      window.CoachNowBusy?.clearBusy(els.joinPayBtn);
    }
  });

  document.querySelectorAll('[data-go-step]').forEach((btn) => {
    btn.addEventListener('click', () => {
      goToStep(Number(btn.dataset.goStep));
    });
  });

  if (els.startAnother) {
    els.startAnother.addEventListener('click', resetFlow);
  }

  setInterval(syncLiveRequestStatus, 2500);

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
