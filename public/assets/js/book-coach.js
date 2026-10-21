(() => {
  const root = document.getElementById('bookCoachApp');
  if (!root) return;

  const slots = JSON.parse(root.dataset.slots || '[]');
  const bookUrl = root.dataset.bookUrl;
  const joinUrlBase = root.dataset.joinUrl || '/api/group-sessions';
  const coachToken = root.dataset.coach;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const datesEl = document.getElementById('bookDates');
  const timesEl = document.getElementById('bookTimes');
  const fieldEl = document.getElementById('bookField');
  const hintEl = document.getElementById('bookTimeHint');
  const noDates = document.getElementById('bookNoDates');
  const slotSteps = document.getElementById('bookSlotSteps');
  const confirmBtn = document.getElementById('bookConfirmBtn');
  const errorEl = document.getElementById('bookError');
  const successEl = document.getElementById('bookSuccess');
  const successText = document.getElementById('bookSuccessText');
  const sumDate = document.getElementById('sumDate');
  const sumTime = document.getElementById('sumTime');
  const sumField = document.getElementById('sumField');
  const sumPrice = document.getElementById('sumPrice');
  const sumTypeRow = document.getElementById('sumTypeRow');
  const sumType = document.getElementById('sumType');
  const sumAgeRow = document.getElementById('sumAgeRow');
  const sumAge = document.getElementById('sumAge');
  const sumSpotsRow = document.getElementById('sumSpotsRow');
  const sumSpots = document.getElementById('sumSpots');
  const defaultRate = Number(root.dataset.rate || 0);
  const convenienceFee = Number(root.dataset.convenienceFee || 0);
  const focusGroupId = root.dataset.focusGroup ? Number(root.dataset.focusGroup) : null;
  const userName = root.dataset.userName || '';
  const playersEl = document.getElementById('bookPlayers');
  const playerCountEl = document.getElementById('bookPlayerCount');
  const playerNamesEl = document.getElementById('bookPlayerNames');
  const sumTotalRow = document.getElementById('sumTotalRow');
  const sumTotal = document.getElementById('sumTotal');

  const groupRate = root.dataset.groupRate !== '' && root.dataset.groupRate != null ? Number(root.dataset.groupRate) : null;
  const maxGroupSize = Math.max(2, Number(root.dataset.maxGroupSize || 6));
  const modeEl = document.getElementById('bookMode');

  let selectedDate = '';
  let selected = null;
  let playerNames = [userName];
  let bookingMode = 'single';

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[ch]));

  const money = (value) => {
    const n = Number(value || 0);
    return `$${Number.isInteger(n) ? n.toFixed(0) : n.toFixed(2)}`;
  };

  // Joining a coach's public group (yellow slot).
  const isGroupSelection = () => Boolean(selected?.joinable && selected?.group_session_id);

  // Booking an open slot as a new private group for 2+ players.
  const isNewGroup = () => Boolean(selected && !selected.group_session_id && groupRate != null && bookingMode === 'group');

  const usesPlayers = () => isGroupSelection() || isNewGroup();

  const groupPrice = () => {
    if (isNewGroup()) return groupRate;
    return selected?.price_per_player != null ? Number(selected.price_per_player) : defaultRate;
  };

  const currentPlayers = () => playerNames.map((name) => String(name || '').trim()).filter(Boolean);

  function renderMode() {
    if (!modeEl) return;
    const show = Boolean(selected && !selected.group_session_id && groupRate != null);
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

  function updateTotals() {
    if (!sumTotalRow || !sumTotal) return;
    if (!selected) {
      sumTotalRow.hidden = true;
      return;
    }
    const players = usesPlayers() ? Math.max(1, playerNames.length) : 1;
    const session = usesPlayers() ? groupPrice() * players : defaultRate;
    const fee = session > 0 ? convenienceFee : 0;
    sumTotalRow.hidden = false;
    sumTotal.textContent = fee > 0
      ? `${money(session + fee)} (${usesPlayers() ? `${players} × ${money(groupPrice())}` : money(session)} + ${money(fee)} fee)`
      : money(session);
  }

  const byDate = slots.reduce((acc, slot) => {
    (acc[slot.date] ||= []).push(slot);
    return acc;
  }, {});

  const dates = Object.keys(byDate).sort();

  function formatDateLabel(iso) {
    const d = new Date(`${iso}T12:00:00`);
    return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
  }

  function renderDates() {
    datesEl.innerHTML = '';
    if (!dates.length) {
      noDates.hidden = false;
      if (slotSteps) slotSteps.hidden = true;
      return;
    }
    noDates.hidden = true;
    if (slotSteps) slotSteps.hidden = false;
    dates.forEach((date) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'book-chip' + (selectedDate === date ? ' is-selected' : '');
      btn.textContent = formatDateLabel(date);
      btn.addEventListener('click', () => {
        selectedDate = date;
        selected = null;
        renderDates();
        renderTimes();
        updateSummary();
      });
      datesEl.appendChild(btn);
    });
  }

  function renderTimes() {
    timesEl.innerHTML = '';
    if (!selectedDate) {
      hintEl.hidden = false;
      hintEl.textContent = 'Select a date to see open times.';
      return;
    }
    const list = byDate[selectedDate] || [];
    if (!list.length) {
      hintEl.hidden = false;
      hintEl.textContent = 'No open times on this day.';
      return;
    }
    hintEl.hidden = true;
    list.forEach((slot) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      const isFull = Boolean(slot.full) || (slot.group_session_id && !slot.joinable && Number(slot.spots_remaining) <= 0);
      const selectedMatch = selected?.time === slot.time
        && selected?.date === slot.date
        && (selected?.group_session_id || null) === (slot.group_session_id || null);
      btn.className = 'book-chip'
        + (selectedMatch ? ' is-selected' : '')
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
      if (slot.label) btn.title = slot.label;
      if (!isFull) {
        btn.addEventListener('click', () => {
          selected = slot;
          renderTimes();
          updateSummary();
        });
      }
      timesEl.appendChild(btn);
    });
  }

  function setRow(row, valueEl, value) {
    if (!row || !valueEl) return;
    const show = value != null && value !== '';
    row.hidden = !show;
    if (show) valueEl.textContent = value;
  }

  function updateSummary() {
    sumDate.textContent = selectedDate ? formatDateLabel(selectedDate) : '—';
    sumTime.textContent = selected?.time_label || '—';
    const fieldLabel = selected
      ? `${selected.location_name}${selected.location_area ? ' · ' + selected.location_area : ''}`
      : '—';
    sumField.textContent = fieldLabel;

    const isGroup = Boolean(selected?.joinable || (selected?.group_session_id && selected?.is_public));
    if (selected && isGroup) {
      const price = selected.price_per_player != null
        ? Number(selected.price_per_player)
        : defaultRate;
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
        sumPrice.textContent = convenienceFee > 0
          ? `$${Number(price).toFixed(0)} / player + $${convenienceFee.toFixed(0)} fee`
          : `$${Number(price).toFixed(0)} / player`;
      }
    } else if (selected) {
      const newGroup = isNewGroup();
      fieldEl.classList.remove('is-group');
      fieldEl.innerHTML = `<strong>${escapeHtml(selected.location_name)}</strong><span>${escapeHtml(selected.location_area || 'Training location for this slot')}</span>`
        + (newGroup ? '<span>Private group — your places are reserved and not shown to other families.</span>' : '');
      setRow(sumTypeRow, sumType, newGroup ? 'Private group' : '');
      setRow(sumAgeRow, sumAge, '');
      setRow(sumSpotsRow, sumSpots, '');
      if (sumPrice) {
        const price = newGroup ? groupRate : defaultRate;
        const unit = newGroup ? ' / player' : '';
        sumPrice.textContent = convenienceFee > 0
          ? `${money(price)}${unit} + ${money(convenienceFee)} fee`
          : `${money(price)}${unit}`;
      }
    } else {
      fieldEl.classList.remove('is-group');
      fieldEl.innerHTML = `<strong>Pick a time</strong><span>Location and group details appear when you choose a slot.</span>`;
      setRow(sumTypeRow, sumType, '');
      setRow(sumAgeRow, sumAge, '');
      setRow(sumSpotsRow, sumSpots, '');
      if (sumPrice) {
        sumPrice.textContent = convenienceFee > 0
          ? `$${Number(defaultRate).toFixed(0)} + $${convenienceFee.toFixed(0)} fee`
          : `$${Number(defaultRate).toFixed(0)}`;
      }
    }

    if (confirmBtn) {
      const canBook = Boolean(selected) && !selected.full && (selected.joinable || !selected.group_session_id);
      confirmBtn.disabled = !canBook;
      confirmBtn.textContent = selected?.joinable ? 'Join Group Session' : 'Confirm booking';
    }
    errorEl.hidden = true;
    renderMode();
    renderPlayers();
    updateTotals();
  }

  const payPanel = document.getElementById('bookPayPanel');
  const payMount = document.getElementById('bookStripeMount');
  const payConfirmBtn = document.getElementById('bookPayConfirmBtn');
  const payCancelBtn = document.getElementById('bookPayCancelBtn');
  const payHint = document.getElementById('bookPayHint');
  let activeCheckout = null;

  const hidePayPanel = () => {
    if (payPanel) payPanel.hidden = true;
    if (confirmBtn) confirmBtn.hidden = false;
    activeCheckout = null;
  };

  payCancelBtn?.addEventListener('click', () => {
    hidePayPanel();
    renderPlayers();
    confirmBtn.disabled = false;
  });

  payConfirmBtn?.addEventListener('click', async () => {
    if (!activeCheckout) return;
    payConfirmBtn.disabled = true;
    errorEl.hidden = true;
    try {
      const booking = await activeCheckout.confirm();
      document.querySelector('.book-summary')?.setAttribute('hidden', '');
      successEl.hidden = false;
      const playerCount = Number(booking.data?.players || 1);
      successText.textContent = `Confirmed with ${booking.data?.coach || 'your coach'} on ${booking.data?.date || selected.date} at ${selected.time_label}${booking.data?.location ? ' · ' + booking.data.location : ''}${playerCount > 1 ? ` · ${playerCount} players` : ''}.`;
    } catch (err) {
      errorEl.hidden = false;
      errorEl.textContent = err.message || 'Payment failed.';
      payConfirmBtn.disabled = false;
    }
  });

  confirmBtn?.addEventListener('click', async () => {
    if (!selected) return;
    confirmBtn.disabled = true;
    errorEl.hidden = true;
    try {
      const isGroup = isGroupSelection();
      const newGroup = isNewGroup();
      const players = currentPlayers();
      if ((isGroup || newGroup) && players.length !== playerNames.length) {
        throw new Error('Enter a name for each player.');
      }
      const newGroupPayload = newGroup ? { new_group: true, players } : {};

      const groupTotal = isGroup ? groupPrice() * players.length : 0;
      const stripeOn = window.CoachNowStripe && await window.CoachNowStripe.enabled();

      if (isGroup && !(stripeOn && groupTotal > 0)) {
        const res = await fetch(`${joinUrlBase}/${selected.group_session_id}/join`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ players }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Could not join this group session.');
        document.querySelector('.book-summary')?.setAttribute('hidden', '');
        successEl.hidden = false;
        successText.textContent = `${data.message || 'You’re in.'} ${formatDateLabel(selected.date)} at ${selected.time_label}${selected.location_name ? ' · ' + selected.location_name : ''}.`;
        return;
      }

      if (stripeOn && payMount && payPanel) {
        activeCheckout = await window.CoachNowStripe.payAndBook({
          coach: coachToken,
          date: selected.date,
          time: selected.time,
          location_id: selected.location_id,
          duration_minutes: selected.duration_minutes,
          ...(isGroup ? { group_session_id: selected.group_session_id, players } : {}),
          ...newGroupPayload,
        }, payMount);
        if (payHint) {
          const fee = Number(activeCheckout.convenience_fee ?? convenienceFee ?? 0);
          payHint.textContent = fee > 0
            ? `Pay ${money(activeCheckout.amount)} securely (includes one ${money(fee)} CoachNow fee).`
            : `Pay ${money(activeCheckout.amount)} securely in CoachNow.`;
        }
        confirmBtn.hidden = true;
        if (playersEl) playersEl.hidden = true;
        payPanel.hidden = false;
        payConfirmBtn.disabled = false;
        return;
      }

      const res = await fetch(bookUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          coach: coachToken,
          date: selected.date,
          time: selected.time,
          location_id: selected.location_id,
          duration_minutes: selected.duration_minutes,
          ...newGroupPayload,
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.message || 'Could not complete booking.');
      document.querySelector('.book-summary')?.setAttribute('hidden', '');
      successEl.hidden = false;
      const bookedPlayers = Number(data.data?.players || 1);
      successText.textContent = `Confirmed with ${data.data?.coach || 'your coach'} on ${data.data?.date || selected.date} at ${selected.time_label}${data.data?.location ? ' · ' + data.data.location : ''}${bookedPlayers > 1 ? ` · private group of ${bookedPlayers}` : ''}.`;
    } catch (err) {
      errorEl.hidden = false;
      errorEl.textContent = err.message || 'Booking failed.';
      confirmBtn.disabled = false;
    }
  });

  if (focusGroupId) {
    const focusSlot = slots.find((s) => Number(s.group_session_id) === focusGroupId && s.joinable);
    if (focusSlot) {
      selectedDate = focusSlot.date;
      selected = focusSlot;
    }
  }

  renderDates();
  renderTimes();
  updateSummary();
})();
