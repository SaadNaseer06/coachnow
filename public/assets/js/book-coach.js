(() => {
  const root = document.getElementById('bookCoachApp');
  if (!root) return;

  const slots = JSON.parse(root.dataset.slots || '[]');
  const bookUrl = root.dataset.bookUrl;
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

  let selectedDate = '';
  let selected = null;

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
      btn.className = 'book-chip' + (selected?.time === slot.time && selected?.date === slot.date ? ' is-selected' : '');
      btn.textContent = slot.time_label;
      btn.addEventListener('click', () => {
        selected = slot;
        renderTimes();
        updateSummary();
      });
      timesEl.appendChild(btn);
    });
  }

  function updateSummary() {
    sumDate.textContent = selectedDate ? formatDateLabel(selectedDate) : '—';
    sumTime.textContent = selected?.time_label || '—';
    const fieldLabel = selected
      ? `${selected.location_name}${selected.location_area ? ' · ' + selected.location_area : ''}`
      : '—';
    sumField.textContent = fieldLabel;
    if (selected) {
      fieldEl.innerHTML = `<strong>${selected.location_name}</strong><span>${selected.location_area || 'Training location for this slot'}</span>`;
    } else {
      fieldEl.innerHTML = `<strong>Pick a time</strong><span>Location appears when you choose a slot.</span>`;
    }
    confirmBtn.disabled = !selected;
    errorEl.hidden = true;
  }

  confirmBtn?.addEventListener('click', async () => {
    if (!selected) return;
    confirmBtn.disabled = true;
    errorEl.hidden = true;
    try {
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
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(data.message || 'Could not complete booking.');
      }
      document.querySelector('.book-summary')?.setAttribute('hidden', '');
      successEl.hidden = false;
      successText.textContent = `Confirmed with ${data.data?.coach || 'your coach'} on ${data.data?.date || selected.date} at ${selected.time_label}${data.data?.location ? ' · ' + data.data.location : ''}.`;
    } catch (err) {
      errorEl.hidden = false;
      errorEl.textContent = err.message || 'Booking failed.';
      confirmBtn.disabled = false;
    }
  });

  renderDates();
  renderTimes();
  updateSummary();
})();
