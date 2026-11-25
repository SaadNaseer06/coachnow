(() => {
  const root = document.getElementById('bookCoachApp');
  if (!root) return;

  const seedSlots = JSON.parse(root.dataset.slots || '[]');
  const bookUrl = root.dataset.bookUrl;
  const bookMultiUrl = root.dataset.bookMultiUrl;
  const slotsUrl = root.dataset.slotsUrl;
  const joinUrlBase = root.dataset.joinUrl || '/api/group-sessions';
  const coachToken = root.dataset.coach;
  const horizon = root.dataset.horizon || '';
  const maxSessions = Number(root.dataset.maxSessions || 24);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const $ = (id) => document.getElementById(id);
  const timesEl = $('bookTimes');
  const fieldEl = $('bookField');
  const hintEl = $('bookTimeHint');
  const noDates = $('bookNoDates');
  const confirmBtn = $('bookConfirmBtn');
  const errorEl = $('bookError');
  const successEl = $('bookSuccess');
  const successText = $('bookSuccessText');
  const sumDate = $('sumDate');
  const sumTime = $('sumTime');
  const sumField = $('sumField');
  const sumPrice = $('sumPrice');
  const sumTypeRow = $('sumTypeRow');
  const sumType = $('sumType');
  const sumAgeRow = $('sumAgeRow');
  const sumAge = $('sumAge');
  const sumSpotsRow = $('sumSpotsRow');
  const sumSpots = $('sumSpots');
  const sumSessionsRow = $('sumSessionsRow');
  const sumSessions = $('sumSessions');
  const defaultRate = Number(root.dataset.rate || 0);
  // CoachNow's fee per session for non-members (0 for members). The session itself is paid to the coach.
  const convenienceFee = Number(root.dataset.convenienceFee || 0);
  const isMember = root.dataset.member === '1';
  // Launch offer, e.g. "$2 booking fee waived through February 28". Empty when there's none.
  const feeWaiver = root.dataset.feeWaiver || '';
  const coachName = root.dataset.coachName || 'your coach';
  const focusGroupId = root.dataset.focusGroup ? Number(root.dataset.focusGroup) : null;
  const userName = root.dataset.userName || '';
  const playersEl = $('bookPlayers');
  const playerCountEl = $('bookPlayerCount');
  const playerNamesEl = $('bookPlayerNames');
  const sumTotalRow = $('sumTotalRow');
  const sumTotal = $('sumTotal');
  const sumFeeRow = $('sumFeeRow');
  const sumFee = $('sumFee');

  const groupRate = root.dataset.groupRate !== '' && root.dataset.groupRate != null ? Number(root.dataset.groupRate) : null;
  const maxGroupSize = Math.max(2, Number(root.dataset.maxGroupSize || 6));
  const modeEl = $('bookMode');

  // Calendar / repeat elements.
  const calGrid = $('bookCalGrid');
  const calMonth = $('bookCalMonth');
  const calStatus = $('bookCalStatus');
  const calPrev = $('bookCalPrev');
  const calNext = $('bookCalNext');
  const repeatEl = $('bookRepeat');
  const repeatLabel = $('bookRepeatLabel');
  const repeatResult = $('bookRepeatResult');
  const repeatWeeks = $('bookRepeatWeeks');

  // —— Training type (step 1): decides which slots show and how they book ——
  const services = JSON.parse(root.dataset.services || '[]');
  const servicesEl = $('bookServices');
  const afterService = $('bookAfterService');
  const teamPanel = $('bookTeamPanel');
  let service = null;
  const serviceKind = () => service?.kind || 'private';

  // Which slots belong to the chosen training type.
  const slotVisible = (slot) => {
    switch (serviceKind()) {
      case 'group':
        return Boolean(slot.group_session_id) || groupRate != null;
      case 'camp':
        return Boolean(slot.group_session_id) && /camp|clinic/i.test(slot.session_type || '');
      default:
        return !slot.group_session_id;
    }
  };

  let selectedDate = '';
  // Group join / private group: one slot. 1-on-1: `basket` may hold several.
  let selected = null;
  let basket = [];
  let recurring = false;
  let playerNames = [userName];
  let bookingMode = 'single';

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[ch]));

  const money = (value) => {
    const n = Number(value || 0);
    return `$${Number.isInteger(n) ? n.toFixed(0) : n.toFixed(2)}`;
  };

  const pad = (n) => String(n).padStart(2, '0');
  const isoDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const todayIso = isoDate(new Date());
  const slotKey = (s) => `${s.date}|${s.time}|${s.group_session_id || ''}`;

  // —— Slots by date, loaded a month at a time ——
  const byDate = {};
  const loadedMonths = new Set();
  const addSlots = (list) => {
    list.forEach((slot) => {
      const day = (byDate[slot.date] ||= []);
      if (!day.some((s) => slotKey(s) === slotKey(slot))) day.push(slot);
    });
    Object.values(byDate).forEach((day) => day.sort((a, b) => a.time.localeCompare(b.time)));
  };
  addSlots(seedSlots);

  const monthKey = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;

  async function loadMonth(firstOfMonth) {
    const key = monthKey(firstOfMonth);
    if (loadedMonths.has(key)) return;
    const last = new Date(firstOfMonth.getFullYear(), firstOfMonth.getMonth() + 1, 0);
    const res = await fetch(`${slotsUrl}?from=${isoDate(firstOfMonth)}&to=${isoDate(last)}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('Could not load open times.');
    const json = await res.json();
    // A month replaces the seed for its dates (fresh availability).
    Object.keys(byDate).filter((d) => d.startsWith(key)).forEach((d) => delete byDate[d]);
    addSlots(json.slots || []);
    loadedMonths.add(key);
  }

  const bookable = (slot) => !(slot.full || (slot.group_session_id && !slot.joinable));

  // Joining a coach's public group (yellow slot).
  const isGroupSelection = () => Boolean(selected?.joinable && selected?.group_session_id);

  // Booking an open slot as a new private group for 2+ players.
  const isNewGroup = () => Boolean(selected && !selected.group_session_id && groupRate != null && bookingMode === 'group');

  const usesPlayers = () => isGroupSelection() || isNewGroup();

  // Several 1-on-1 sessions in one checkout.
  const isMulti = () => !usesPlayers() && basket.length > 1;

  const groupPrice = () => {
    if (isNewGroup()) return groupRate;
    return selected?.price_per_player != null ? Number(selected.price_per_player) : defaultRate;
  };

  const currentPlayers = () => playerNames.map((name) => String(name || '').trim()).filter(Boolean);

  function formatDateLabel(iso) {
    const d = new Date(`${iso}T12:00:00`);
    return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
  }

  const slotLabel = (s) => `${formatDateLabel(s.date)} · ${String(s.time_label || s.time).replace(/ · .*$/, '')}`;

  // —— Month calendar ——
  let cursor = new Date();
  cursor = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
  const horizonDate = horizon ? new Date(`${horizon}T12:00:00`) : null;

  async function showMonth(first) {
    cursor = first;
    calMonth.textContent = first.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    calPrev.disabled = monthKey(first) <= monthKey(new Date());
    calNext.disabled = Boolean(horizonDate) && monthKey(new Date(first.getFullYear(), first.getMonth() + 1, 1)) > monthKey(horizonDate);
    calStatus.textContent = 'Loading open times…';
    renderCalendar();
    try {
      await loadMonth(first);
      calStatus.textContent = '';
    } catch (err) {
      calStatus.textContent = err.message;
    }
    renderCalendar();
  }

  function renderCalendar() {
    calGrid.innerHTML = '';
    const first = cursor;
    const lead = (first.getDay() + 6) % 7; // Monday-first grid
    const daysInMonth = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
    for (let i = 0; i < lead; i += 1) calGrid.appendChild(document.createElement('span'));

    let openDays = 0;
    for (let day = 1; day <= daysInMonth; day += 1) {
      const iso = isoDate(new Date(first.getFullYear(), first.getMonth(), day));
      const slots = (byDate[iso] || []).filter(bookable).filter(slotVisible);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = String(day);
      btn.className = 'book-cal__day';
      const outOfRange = iso < todayIso || (horizon && iso > horizon);
      if (outOfRange || !slots.length) {
        btn.disabled = true;
      } else {
        openDays += 1;
        btn.classList.add('is-open');
        if (slots.some((s) => s.joinable)) btn.classList.add('has-group');
        btn.setAttribute('aria-label', `${formatDateLabel(iso)} — ${slots.length} open time${slots.length === 1 ? '' : 's'}`);
        btn.addEventListener('click', () => {
          selectedDate = iso;
          renderCalendar();
          renderTimes();
          updateSummary();
        });
      }
      if (iso === selectedDate) btn.classList.add('is-selected');
      if (basket.some((s) => s.date === iso) || (selected && selected.date === iso && usesPlayers())) btn.classList.add('is-picked');
      if (iso === todayIso) btn.classList.add('is-today');
      calGrid.appendChild(btn);
    }
    if (noDates) {
      noDates.hidden = openDays > 0 || calStatus.textContent !== '';
      const what = { group: 'open group sessions', camp: 'camps or clinics' }[serviceKind()] || 'open times';
      noDates.firstChild.textContent = calNext.disabled && horizon
        ? `No ${what} this month — bookings open up to ${formatDateLabel(horizon)}. You can still `
        : `No ${what} this month. Try the next month, or `;
    }
  }

  calPrev?.addEventListener('click', () => showMonth(new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1)));
  calNext?.addEventListener('click', () => showMonth(new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1)));

  // —— Times for the selected day ——
  function inBasket(slot) {
    return basket.some((s) => slotKey(s) === slotKey(slot));
  }

  const toMinutes = (time) => {
    const [h, m] = String(time).split(':').map(Number);
    return h * 60 + (m || 0);
  };
  const overlaps = (a, b) => {
    const aStart = toMinutes(a.time);
    const bStart = toMinutes(b.time);
    return aStart < bStart + Number(b.duration_minutes || 60) && bStart < aStart + Number(a.duration_minutes || 60);
  };

  function pickSlot(slot) {
    if (slot.joinable && slot.group_session_id) {
      // Group join is its own single-slot checkout.
      basket = [];
      recurring = false;
      selected = slot;
    } else if (bookingMode === 'group' && groupRate != null) {
      basket = [];
      recurring = false;
      selected = slot;
    } else if (inBasket(slot)) {
      basket = basket.filter((s) => slotKey(s) !== slotKey(slot));
      selected = basket[basket.length - 1] || null;
    } else {
      if (selected && usesPlayers()) selected = null;
      // Start times are 15 minutes apart, so a new pick can overlap one already chosen
      // that day. The new time replaces it instead of failing at checkout.
      basket = basket.filter((s) => s.date !== slot.date || s.group_session_id || !overlaps(s, slot));
      if (basket.length >= maxSessions) {
        showError(`You can book up to ${maxSessions} sessions at once.`);
        return;
      }
      basket.push(slot);
      selected = slot;
    }
    renderCalendar();
    renderTimes();
    updateSummary();
  }

  // The hour whose quarter-hour start times are showing, as "date|HH".
  let openHour = '';
  const hourLabel = (hh) => {
    const h = Number(hh);
    return `${h % 12 || 12} ${h < 12 ? 'AM' : 'PM'}`;
  };
  const timeOnly = (slot) => String(slot.time_label || slot.time).replace(/ · .*$/, '');

  function renderTimes() {
    timesEl.innerHTML = '';
    if (!selectedDate) {
      hintEl.hidden = false;
      hintEl.textContent = 'Select a date to see open times.';
      return;
    }
    const list = (byDate[selectedDate] || []).filter(slotVisible);
    if (!list.length) {
      hintEl.hidden = false;
      hintEl.textContent = 'No open times on this day.';
      return;
    }
    hintEl.hidden = true;

    // Group sessions keep their own larger yellow buttons. 1-on-1 times are grouped by
    // hour to keep the list short; tapping an hour shows its start times.
    const items = [];
    const hours = new Map();
    list.forEach((slot) => {
      if (slot.group_session_id) {
        items.push({ at: slot.time, slot });
        return;
      }
      const hh = String(slot.time).slice(0, 2);
      if (!hours.has(hh)) {
        hours.set(hh, []);
        items.push({ at: `${hh}:00`, hour: hh });
      }
      hours.get(hh).push(slot);
    });
    items.sort((a, b) => a.at.localeCompare(b.at));

    items.forEach((item) => {
      if (item.slot) {
        timesEl.appendChild(slotChip(item.slot));
        return;
      }
      const slots = hours.get(item.hour);
      const key = `${selectedDate}|${item.hour}`;
      const open = openHour === key;
      const picked = slots.find((s) => inBasket(s) || (selected && slotKey(selected) === slotKey(s)));
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `book-chip book-chip--hour${picked ? ' is-selected' : ''}${open ? ' is-open' : ''}`;
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.innerHTML = `${escapeHtml(picked ? timeOnly(picked) : hourLabel(item.hour))}<span class="book-chip__caret" aria-hidden="true"></span>`;
      btn.addEventListener('click', () => {
        openHour = open ? '' : key;
        renderTimes();
      });
      timesEl.appendChild(btn);

      if (open) {
        const menu = document.createElement('div');
        menu.className = 'book-quarters';
        menu.setAttribute('role', 'group');
        menu.setAttribute('aria-label', `Start times from ${hourLabel(item.hour)}`);
        slots.forEach((s) => menu.appendChild(slotChip(s, () => { openHour = ''; })));
        timesEl.appendChild(menu);
      }
    });
  }

  function slotChip(slot, beforePick) {
      const btn = document.createElement('button');
      btn.type = 'button';
      const isFull = !bookable(slot);
      const isPicked = inBasket(slot) || (selected && slotKey(selected) === slotKey(slot));
      btn.className = 'book-chip'
        + (isPicked ? ' is-selected' : '')
        + (slot.joinable ? ' is-group' : '')
        + (isFull ? ' is-full' : '');
      if (isFull) {
        btn.textContent = `${String(slot.time_label || '').replace(' · Full', '').replace(' · Group', '')} · Full`;
        btn.disabled = true;
        btn.setAttribute('aria-disabled', 'true');
      } else if (slot.joinable) {
        btn.textContent = `${String(slot.time_label || '').replace(' · Group', '')} · ${slot.spots_remaining} left`;
      } else {
        btn.textContent = slot.time_label;
      }
      btn.setAttribute('aria-pressed', isPicked ? 'true' : 'false');
      if (slot.label) btn.title = slot.label;
      if (!isFull) {
        btn.addEventListener('click', () => {
          beforePick?.();
          pickSlot(slot);
        });
      }
      return btn;
  }

  // —— Weekly repeat ——
  let repeatAnchorKey = '';

  function syncRepeat() {
    if (!repeatEl) return;
    const anchor = !usesPlayers() ? basket[0] : null;
    repeatEl.hidden = !anchor;
    if (!anchor) return;
    const d = new Date(`${anchor.date}T12:00:00`);
    repeatLabel.textContent = `Every week at ${String(anchor.time_label || anchor.time).replace(/ · .*$/, '')}, starting ${formatDateLabel(anchor.date)}.`;
    // New first session → default to its weekday again (don't keep a stale day).
    const key = slotKey(anchor);
    if (key !== repeatAnchorKey) {
      repeatAnchorKey = key;
      repeatEl.querySelectorAll('input[type="checkbox"]').forEach((b) => { b.checked = Number(b.value) === d.getDay(); });
      if (repeatResult) repeatResult.textContent = '';
    }
  }

  $('bookRepeatAdd')?.addEventListener('click', async () => {
    const anchor = basket[0];
    if (!anchor) return;
    const days = [...repeatEl.querySelectorAll('input:checked')].map((b) => Number(b.value));
    if (!days.length) {
      repeatResult.textContent = 'Pick at least one day of the week.';
      return;
    }
    const weeks = Number(repeatWeeks.value || 4);
    const start = new Date(`${anchor.date}T12:00:00`);
    const end = new Date(start);
    end.setDate(end.getDate() + weeks * 7 - 1);

    repeatResult.textContent = 'Checking the coach’s availability…';
    try {
      for (let m = new Date(start.getFullYear(), start.getMonth(), 1); m <= end; m = new Date(m.getFullYear(), m.getMonth() + 1, 1)) {
        // eslint-disable-next-line no-await-in-loop
        await loadMonth(m);
      }
    } catch (err) {
      repeatResult.textContent = err.message;
      return;
    }

    const wanted = [];
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      const iso = isoDate(d);
      if (!days.includes(d.getDay()) || (horizon && iso > horizon)) continue;
      wanted.push(iso);
    }

    const added = [];
    const skipped = [];
    wanted.forEach((iso) => {
      const day = (byDate[iso] || []).filter((s) => bookable(s) && !s.group_session_id && s.time === anchor.time);
      const match = day.find((s) => Number(s.location_id) === Number(anchor.location_id)) || day[0];
      if (!match) {
        skipped.push(iso);
      } else if (!inBasket(match) && basket.length < maxSessions) {
        basket.push(match);
        added.push(iso);
      }
    });
    basket.sort((a, b) => (`${a.date} ${a.time}`).localeCompare(`${b.date} ${b.time}`));
    recurring = basket.length > 1;

    repeatResult.textContent = `${basket.length} weekly session${basket.length === 1 ? '' : 's'} selected.`
      + (skipped.length ? ` Not available (coach is busy or off): ${skipped.map(formatDateLabel).join(', ')}.` : '');
    renderCalendar();
    renderTimes();
    updateSummary();
  });

  // —— Players (group join / private group) ——
  function renderMode() {
    if (!modeEl) return;
    // The training type (step 1) now decides 1-on-1 vs private group.
    const show = false;
    modeEl.hidden = !show;
    modeEl.querySelectorAll('[data-book-mode]').forEach((btn) => {
      const on = btn.dataset.bookMode === bookingMode;
      btn.classList.toggle('is-selected', on);
      btn.setAttribute('aria-checked', on ? 'true' : 'false');
    });
  }

  modeEl?.querySelectorAll('[data-book-mode]').forEach((btn) => {
    btn.addEventListener('click', () => {
      bookingMode = btn.dataset.bookMode === 'group' ? 'group' : 'single';
      if (bookingMode === 'group') {
        selected = basket[0] || selected;
        basket = [];
        recurring = false;
      } else if (selected && !selected.group_session_id) {
        basket = [selected];
      }
      renderTimes();
      updateSummary();
    });
  });

  function renderPlayers() {
    if (!playersEl || !playerCountEl || !playerNamesEl) return;
    if (!usesPlayers()) {
      playersEl.hidden = true;
      return;
    }
    playersEl.hidden = false;
    const min = isNewGroup() ? 2 : 1;
    const max = isNewGroup()
      ? maxGroupSize
      : Math.max(1, Math.min(10, Number(selected.spots_remaining) || 1));
    while (playerNames.length < min) playerNames.push('');
    if (playerNames.length > max) playerNames = playerNames.slice(0, max);

    playerCountEl.innerHTML = '';
    for (let i = min; i <= max; i += 1) {
      const opt = document.createElement('option');
      opt.value = String(i);
      opt.textContent = `${i} player${i === 1 ? '' : 's'}`;
      opt.selected = i === playerNames.length;
      playerCountEl.appendChild(opt);
    }

    playerNamesEl.innerHTML = '';
    playerNames.forEach((name, index) => {
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'book-players__input';
      input.maxLength = 120;
      input.placeholder = `Player ${index + 1} name`;
      input.value = name;
      input.setAttribute('aria-label', `Player ${index + 1} name`);
      input.addEventListener('input', () => {
        playerNames[index] = input.value;
        updateTotals();
      });
      playerNamesEl.appendChild(input);
    });
  }

  playerCountEl?.addEventListener('change', () => {
    const count = Number(playerCountEl.value) || 1;
    while (playerNames.length < count) playerNames.push('');
    playerNames = playerNames.slice(0, count);
    renderPlayers();
    updateTotals();
  });

  // —— Summary ——
  function updateTotals() {
    if (!sumTotalRow || !sumTotal) return;
    if (!selected && !basket.length) {
      sumTotalRow.hidden = true;
      if (sumFeeRow) sumFeeRow.hidden = true;
      return;
    }
    let session;
    let breakdown;
    let sessions = 1;
    if (usesPlayers()) {
      const players = Math.max(1, playerNames.length);
      session = groupPrice() * players;
      breakdown = `${players} × ${money(groupPrice())}`;
    } else {
      sessions = Math.max(1, basket.length);
      session = defaultRate * sessions;
      breakdown = sessions > 1 ? `${sessions} × ${money(defaultRate)}` : money(session);
    }
    sumTotalRow.hidden = false;
    sumTotal.textContent = breakdown !== money(session) ? `${money(session)} (${breakdown})` : money(session);

    if (!sumFeeRow || !sumFee) return;
    const fee = session > 0 ? convenienceFee * sessions : 0;
    sumFeeRow.hidden = !(fee > 0 || ((isMember || feeWaiver) && session > 0));
    sumFee.textContent = fee > 0
      ? (sessions > 1 ? `${money(fee)} (${sessions} × ${money(convenienceFee)})` : money(fee))
      : (isMember ? 'None, you’re a member' : 'Waived');
  }

  function renderSessionList() {
    if (!sumSessionsRow || !sumSessions) return;
    const show = isMulti();
    sumSessionsRow.hidden = !show;
    sumDate.closest('div').hidden = show;
    sumTime.closest('div').hidden = show;
    if (!show) return;
    sumSessions.innerHTML = '';
    basket.forEach((slot) => {
      const li = document.createElement('li');
      li.innerHTML = `<span>${escapeHtml(slotLabel(slot))}</span>`;
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'book-sessions__remove';
      rm.setAttribute('aria-label', `Remove ${slotLabel(slot)}`);
      rm.textContent = '×';
      rm.addEventListener('click', () => pickSlot(slot));
      li.appendChild(rm);
      sumSessions.appendChild(li);
    });
  }

  function setRow(row, valueEl, value) {
    if (!row || !valueEl) return;
    const show = value != null && value !== '';
    row.hidden = !show;
    if (show) valueEl.textContent = value;
  }

  function showError(message) {
    errorEl.hidden = false;
    errorEl.textContent = message;
  }

  function updateSummary() {
    // The payment form belongs to the old picks.
    if (activeCheckout && !activeCheckout.isPaid && !payConfirmBtn?.disabled) hidePayPanel();
    const multiHint = $('bookMultiHint');
    if (multiHint) multiHint.hidden = serviceKind() !== 'private';
    const current = selected || basket[basket.length - 1] || null;
    sumDate.textContent = current ? formatDateLabel(current.date) : (selectedDate ? formatDateLabel(selectedDate) : '—');
    sumTime.textContent = current?.time_label || '—';
    sumField.textContent = current
      ? `${current.location_name}${current.location_area ? ' · ' + current.location_area : ''}`
      : '—';

    const isGroup = Boolean(selected?.joinable || (selected?.group_session_id && selected?.is_public));
    if (selected && isGroup) {
      const price = selected.price_per_player != null ? Number(selected.price_per_player) : defaultRate;
      const booked = selected.booked_count != null ? selected.booked_count : '—';
      const max = selected.max_players != null ? selected.max_players : '—';
      const spots = selected.spots_remaining != null ? selected.spots_remaining : '—';
      fieldEl.classList.add('is-group');
      fieldEl.innerHTML = `
        <strong>Group training</strong>
        <div class="book-field__meta">
          <span><strong>Location:</strong> ${escapeHtml(selected.location_name)}${selected.location_area ? ' · ' + escapeHtml(selected.location_area) : ''}</span>
          ${selected.sport ? `<span><strong>Sport:</strong> ${escapeHtml(selected.sport)}</span>` : ''}
          ${selected.age_range ? `<span><strong>Ages:</strong> ${escapeHtml(selected.age_range)}</span>` : ''}
          <span><strong>Price:</strong> ${money(price)} per player</span>
          <span><strong>Booked:</strong> ${booked} of ${max}</span>
          <span><strong>Available spaces:</strong> ${spots}</span>
        </div>
      `;
      setRow(sumTypeRow, sumType, selected.session_type || 'Group training');
      setRow(sumAgeRow, sumAge, selected.age_range || '');
      setRow(sumSpotsRow, sumSpots, `${booked} booked · ${spots} left`);
      if (sumPrice) {
        sumPrice.textContent = `${money(price)} / player`;
      }
    } else if (current) {
      const newGroup = isNewGroup();
      fieldEl.classList.remove('is-group');
      fieldEl.innerHTML = `<strong>${escapeHtml(current.location_name)}</strong><span>${escapeHtml(current.location_area || 'Training location for this slot')}</span>`
        + (newGroup ? '<span>Private group — your places are reserved and not shown to other families.</span>' : '')
        + (isMulti() ? `<span>${basket.length} sessions selected${recurring ? ' (weekly)' : ''}, booked together.</span>` : '');
      const typeLabel = newGroup ? 'Private group' : (service && service.session_type !== 'Private 1-on-1' ? service.label : '');
      setRow(sumTypeRow, sumType, [typeLabel, isMulti() && recurring ? 'weekly' : ''].filter(Boolean).join(' · '));
      setRow(sumAgeRow, sumAge, '');
      setRow(sumSpotsRow, sumSpots, '');
      if (sumPrice) {
        const price = newGroup ? groupRate : defaultRate;
        const unit = newGroup ? ' / player' : (isMulti() ? ' / session' : '');
        sumPrice.textContent = `${money(price)}${unit}`;
      }
    } else {
      fieldEl.classList.remove('is-group');
      fieldEl.innerHTML = '<strong>Pick a time</strong><span>Location and group details appear when you choose a slot.</span>';
      setRow(sumTypeRow, sumType, '');
      setRow(sumAgeRow, sumAge, '');
      setRow(sumSpotsRow, sumSpots, '');
      if (sumPrice) {
        // Groups and camps are priced per player, per session — not the 1-on-1 rate.
        sumPrice.textContent = ['group', 'camp'].includes(serviceKind())
          ? 'Per player — pick a time'
          : money(defaultRate);
      }
    }

    if (confirmBtn) {
      const canBook = usesPlayers() ? Boolean(selected) && !selected.full : basket.length > 0;
      confirmBtn.disabled = !canBook;
      confirmBtn.textContent = selected?.joinable
        ? 'Join Group Session'
        : (isMulti() ? `Book ${basket.length} sessions` : 'Confirm booking');
    }
    errorEl.hidden = true;
    renderMode();
    renderPlayers();
    renderSessionList();
    syncRepeat();
    updateTotals();
  }

  // —— Checkout ——
  const payPanel = $('bookPayPanel');
  const payMount = $('bookStripeMount');
  const payConfirmBtn = $('bookPayConfirmBtn');
  const payCancelBtn = $('bookPayCancelBtn');
  const payHint = $('bookPayHint');
  let activeCheckout = null;

  const hidePayPanel = () => {
    if (payPanel) payPanel.hidden = true;
    if (confirmBtn) confirmBtn.hidden = false;
    if (payCancelBtn) payCancelBtn.hidden = false;
    activeCheckout = null;
  };

  const slotPayload = (s) => ({
    date: s.date,
    time: s.time,
    location_id: s.location_id,
    duration_minutes: s.duration_minutes,
  });

  function showSuccess(data, fallbackSlot) {
    document.querySelector('.book-summary')?.setAttribute('hidden', '');
    successEl.hidden = false;
    const d = data?.data || {};
    if (Number(d.sessions || 0) > 1) {
      successText.textContent = `${d.sessions} sessions booked with ${d.coach || 'your coach'}${d.series ? ' (weekly)' : ''}: ${(d.dates || []).join(', ')}.`;
    } else {
      const playerCount = Number(d.players || 1);
      const slot = fallbackSlot || {};
      successText.textContent = `Confirmed with ${d.coach || 'your coach'} on ${formatDateLabel(d.date || slot.date)} at ${slot.time_label || ''}${d.location ? ' · ' + d.location : ''}${playerCount > 1 ? ` · ${playerCount} players` : ''}.`;
    }
    showHowToPay(d);
  }

  // The coach is paid directly, so this is the parent's next step.
  function showHowToPay(d) {
    const box = $('bookPayCoach');
    const due = Number(d.due_to_coach || 0);
    if (!box || due <= 0) return;
    const phone = String(d.coach_phone || '').trim();
    const digits = phone.replace(/[^0-9+]/g, '');
    const lines = [`<p><strong>Pay ${escapeHtml(d.coach || 'your coach')} ${money(due)} directly.</strong></p>`];
    lines.push(d.how_to_pay
      ? `<p><strong>How to pay:</strong> <span class="book-pay-coach__note">${escapeHtml(d.how_to_pay)}</span></p>`
      : `<p>${escapeHtml(d.coach || 'Your coach')} will let you know how to pay.</p>`);
    const contact = [];
    if (digits) contact.push(`<a href="sms:${escapeHtml(digits)}">Text ${escapeHtml(phone)}</a>`);
    if (d.coach_email) contact.push(`<a href="mailto:${escapeHtml(d.coach_email)}">${escapeHtml(d.coach_email)}</a>`);
    if (contact.length) lines.push(`<p>${contact.join(' · ')}</p>`);
    lines.push('<p>Cancellations and refunds for session payments are handled by your coach.</p>');
    box.innerHTML = lines.join('');
    box.hidden = false;
  }

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Could not complete booking.');
    return data;
  }

  payCancelBtn?.addEventListener('click', () => {
    hidePayPanel();
    renderPlayers();
    confirmBtn.disabled = false;
  });

  payConfirmBtn?.addEventListener('click', async () => {
    if (!activeCheckout) return;
    payConfirmBtn.disabled = true;
    if (payCancelBtn) payCancelBtn.hidden = true;
    errorEl.hidden = true;
    try {
      const booking = await activeCheckout.confirm();
      showSuccess(booking, selected || basket[0]);
    } catch (err) {
      if (err.pending) {
        // Stripe's webhook books it once the payment clears.
        payPanel.hidden = true;
        showError(err.message);
        return;
      }
      if (err.paid && err.status === 422) {
        // Paid, but the time was taken: the server has already started the refund.
        hidePayPanel();
        renderPlayers();
        confirmBtn.disabled = false;
        showError(err.message);
        return;
      }
      if (err.paid) {
        activeCheckout.isPaid = true;
        payConfirmBtn.textContent = 'Finish booking';
        showError('Your payment went through, but we couldn’t confirm the booking yet. Press Finish booking. You won’t be charged again.');
      } else {
        if (payCancelBtn) payCancelBtn.hidden = false;
        showError(err.message || 'Payment failed.');
      }
      payConfirmBtn.disabled = false;
    }
  });

  confirmBtn?.addEventListener('click', async () => {
    const first = usesPlayers() ? selected : basket[0];
    if (!first) return;
    confirmBtn.disabled = true;
    errorEl.hidden = true;
    try {
      const isGroup = isGroupSelection();
      const newGroup = isNewGroup();
      const multi = isMulti();
      const players = currentPlayers();
      if ((isGroup || newGroup) && players.length !== playerNames.length) {
        throw new Error('Enter a name for each player.');
      }
      const newGroupPayload = newGroup ? { new_group: true, players } : {};
      const sessionType = !usesPlayers() ? (service?.session_type || 'Private 1-on-1') : undefined;
      const multiPayload = multi ? { slots: basket.map(slotPayload), recurring, session_type: sessionType } : {};

      // Non-members pay the CoachNow fee first. The server works out what's owed.
      const feeByCard = convenienceFee > 0 && payMount && payPanel && window.CoachNowStripe && await window.CoachNowStripe.enabled();
      if (feeByCard) {
        const checkout = await window.CoachNowStripe.payAndBook({
          coach: coachToken,
          ...slotPayload(first),
          ...(sessionType ? { session_type: sessionType } : {}),
          ...(isGroup ? { group_session_id: selected.group_session_id, players } : {}),
          ...newGroupPayload,
          ...multiPayload,
        }, payMount);
        if (checkout.requires_payment) {
          activeCheckout = checkout;
          const n = Number(checkout.sessions || 1);
          if (payHint) {
            payHint.textContent = `CoachNow fee: ${money(checkout.amount)}${n > 1 ? ` (${n} sessions × ${money(checkout.fee_per_session)})` : ''}. You’ll pay ${coachName} ${money(checkout.session_amount)} directly for ${n > 1 ? 'the sessions' : 'the session'}.`;
          }
          payConfirmBtn.textContent = `Pay ${money(checkout.amount)} & book`;
          confirmBtn.hidden = true;
          if (playersEl) playersEl.hidden = true;
          payPanel.hidden = false;
          payConfirmBtn.disabled = false;
          return;
        }
      }

      if (isGroup) {
        const joined = await postJson(`${joinUrlBase}/${selected.group_session_id}/join`, { players });
        showSuccess(joined, selected);
        return;
      }

      const data = multi
        ? await postJson(bookMultiUrl, { coach: coachToken, ...multiPayload })
        : await postJson(bookUrl, { coach: coachToken, ...slotPayload(first), ...(sessionType ? { session_type: sessionType } : {}), ...newGroupPayload });
      showSuccess(data, first);
    } catch (err) {
      showError(err.message || 'Booking failed.');
      confirmBtn.disabled = false;
    }
  });

  function chooseService(key, { keepFocus = false } = {}) {
    service = services.find((s) => s.key === key) || null;
    servicesEl?.querySelectorAll('[data-service]').forEach((btn) => {
      const on = btn.dataset.service === key;
      btn.classList.toggle('is-selected', on);
      btn.setAttribute('aria-checked', on ? 'true' : 'false');
    });
    const team = serviceKind() === 'team';
    if (teamPanel) teamPanel.hidden = !team;
    if (afterService) afterService.hidden = !service || team;
    // Group service: an open (non-group) time books as a private group for your players.
    bookingMode = serviceKind() === 'group' && groupRate != null ? 'group' : 'single';
    if (!keepFocus) {
      selected = null;
      selectedDate = '';
      basket = [];
      recurring = false;
    }
    renderCalendar();
    renderTimes();
    updateSummary();
  }

  servicesEl?.querySelectorAll('[data-service]').forEach((btn) => {
    btn.addEventListener('click', () => chooseService(btn.dataset.service));
  });

  // —— Start ——
  let startMonth = cursor;
  if (focusGroupId) {
    const focusSlot = seedSlots.find((s) => Number(s.group_session_id) === focusGroupId && s.joinable);
    if (focusSlot) {
      selectedDate = focusSlot.date;
      selected = focusSlot;
      const d = new Date(`${focusSlot.date}T12:00:00`);
      startMonth = new Date(d.getFullYear(), d.getMonth(), 1);
    }
  }

  const initialService = root.dataset.initialService || (services.length === 1 ? services[0].key : '');
  if (initialService) {
    chooseService(initialService, { keepFocus: Boolean(selected) });
  } else {
    renderTimes();
    updateSummary();
  }
  showMonth(startMonth);
})();
