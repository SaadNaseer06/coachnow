(() => {
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
    notes: '',
  };

  const ACCEPT_WINDOW_SECONDS = 15 * 60;

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
    startAnother: document.getElementById('reqStartAnother'),
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

  function formatDisplayDate(date) {
    return date.toLocaleDateString(undefined, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  }

  function formatShortDate(date) {
    return date.toLocaleDateString(undefined, { weekday: 'short' });
  }

  function formatDayNum(date) {
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
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

  function startCountdown(seconds) {
    if (countdownTimer) clearInterval(countdownTimer);
    let remaining = seconds;

    const tick = () => {
      const m = Math.floor(remaining / 60);
      const s = remaining % 60;
      if (els.countdownDisplay) {
        els.countdownDisplay.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
      }
      if (remaining <= 0) {
        clearInterval(countdownTimer);
        if (els.countdownDisplay) els.countdownDisplay.textContent = '00:00';
        return;
      }
      remaining -= 1;
    };

    tick();
    countdownTimer = setInterval(tick, 1000);
  }

  function renderLiveSummary() {
    if (!els.liveSummary) return;
    const dateStr = state.selectedDate ? formatDisplayDate(state.selectedDate) : state.date;
    els.liveSummary.innerHTML = `
      <div><dt>Location</dt><dd>${escapeHtml(state.locationName)}</dd></div>
      <div><dt>Date</dt><dd>${escapeHtml(dateStr)}</dd></div>
      <div><dt>Time</dt><dd>${escapeHtml(state.selectedTime)}</dd></div>
      <div><dt>Session</dt><dd>${escapeHtml(state.sessionType)}</dd></div>
      <div><dt>Age range</dt><dd>${escapeHtml(state.ageRange)}</dd></div>
      <div><dt>Price / player</dt><dd>${escapeHtml(state.priceRange)}</dd></div>
    `;
  }

  function resetFlow() {
    if (countdownTimer) clearInterval(countdownTimer);
    state.selectedTime = '';
    state.selectedDate = null;
    if (els.form1) els.form1.reset();
    if (els.form4) els.form4.reset();
    if (els.toDetailsBtn) els.toDetailsBtn.disabled = true;
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
      goToStep(4);
    });
  }

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
      state.ageRange = document.getElementById('reqAgeRange')?.value || '';
      state.priceRange = document.getElementById('reqPriceRange')?.value || '';
      state.sessionType = document.getElementById('reqSessionType')?.value || '';
      state.notes = document.getElementById('reqNotes')?.value.trim() || '';

      const idNum = Math.floor(1000 + Math.random() * 9000);
      const requestId = `CN-${idNum}`;
      if (els.requestId) els.requestId.textContent = `#${requestId}`;

      saveSessionRequest(requestId);

      renderLiveSummary();
      startCountdown(ACCEPT_WINDOW_SECONDS);
      goToStep(5);
    });
  }

  function saveSessionRequest(requestId) {
    const dateStr = state.selectedDate
      ? state.selectedDate.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
      : state.date;
    const when = [dateStr, state.selectedTime].filter(Boolean).join(' · ');

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
      status: 'open',
      accept_seconds: ACCEPT_WINDOW_SECONDS,
      posted: 'Just now',
      createdAt: Date.now(),
      acceptExpiresAt: Date.now() + ACCEPT_WINDOW_SECONDS * 1000,
    };

    try {
      const key = 'coachnow_session_requests';
      const existing = JSON.parse(localStorage.getItem(key) || '[]');
      existing.unshift(payload);
      localStorage.setItem(key, JSON.stringify(existing.slice(0, 20)));
    } catch {
      /* ignore storage errors in demo mode */
    }
  }

  document.querySelectorAll('[data-go-step]').forEach((btn) => {
    btn.addEventListener('click', () => {
      goToStep(Number(btn.dataset.goStep));
    });
  });

  if (els.startAnother) {
    els.startAnother.addEventListener('click', resetFlow);
  }

  renderTimeSlots(els.morningSlots, morningSlots);
  renderTimeSlots(els.afternoonSlots, afternoonSlots);
  renderTimeSlots(els.eveningSlots, eveningSlots);
  setMinDate();

  // Initial paint only — no scroll on first load.
  els.steps.forEach((section) => {
    const active = Number(section.dataset.step) === 1;
    section.hidden = !active;
    section.classList.toggle('is-active', active);
  });
})();
