(() => {
  const app = document.getElementById('coachScheduleApp');
  if (!app) return;

  const csrf = app.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
  const calStart = parseInt(app.dataset.calStart || '4', 10);
  const baseUrl = app.dataset.moveUrl || '/coach/schedule/sessions';
  const view = app.dataset.view || 'week';
  const week = app.dataset.week || '';

  const openModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('is-open');
    el.setAttribute('aria-hidden', 'false');
    document.body.classList.add('admin-modal-open');
  };
  const closeModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('is-open');
    el.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.admin-modal.is-open')) {
      document.body.classList.remove('admin-modal-open');
    }
  };

  const isGroupType = (type) => {
    const t = String(type || '').toLowerCase();
    if (!t || t.includes('private') || t.includes('1-on-1') || t.includes('1 on 1')) return false;
    return t.includes('group') || t.includes('camp') || t.includes('clinic') || t.includes('team');
  };

  const syncMaxField = (typeEl, fieldEl) => {
    if (!typeEl || !fieldEl) return;
    fieldEl.hidden = !isGroupType(typeEl.value);
  };

  const bookType = document.getElementById('bookSessionType');
  const bookMaxField = document.getElementById('bookMaxPlayersField');
  bookType?.addEventListener('change', () => syncMaxField(bookType, bookMaxField));
  syncMaxField(bookType, bookMaxField);

  // —— Double-book on create (redirect with errors) ——
  const bookForm = document.getElementById('bookPlayerForm');
  const bookForce = document.getElementById('bookForceDouble');
  let pendingDoubleAction = null;

  if (bookForm?.dataset.doubleBook === '1') {
    pendingDoubleAction = () => {
      if (bookForce) bookForce.value = '1';
      bookForm.submit();
    };
    openModal('doubleBookModal');
  }

  document.getElementById('doubleBookConfirm')?.addEventListener('click', () => {
    closeModal('doubleBookModal');
    if (typeof pendingDoubleAction === 'function') pendingDoubleAction();
    pendingDoubleAction = null;
  });

  document.querySelectorAll('#doubleBookModal [data-admin-modal-close]').forEach((el) => {
    el.addEventListener('click', () => { pendingDoubleAction = null; });
  });

  // —— Manage session ——
  const manageForm = document.getElementById('sessionManageForm');
  const manageType = document.getElementById('manageType');
  const manageMaxField = document.getElementById('manageMaxField');
  const managePlayersWrap = document.getElementById('managePlayersWrap');
  const managePlayersList = document.getElementById('managePlayersList');
  const manageError = document.getElementById('sessionManageError');
  const manageErrorText = document.getElementById('sessionManageErrorText');
  let currentBookingId = null;
  let currentGroupId = null;
  let removeIds = [];
  let addPlayers = [];
  let didDrag = false;

  // Filled after helper definitions below (same tick).
  let applyMoveInUi = () => {};
  let removeBookingFromUi = () => {};
  let applyManageSaveToUi = () => {};

  manageType?.addEventListener('change', () => {
    syncMaxField(manageType, manageMaxField);
    if (managePlayersWrap) managePlayersWrap.hidden = !isGroupType(manageType.value);
  });

  const renderPlayers = (players) => {
    if (!managePlayersList) return;
    managePlayersList.innerHTML = '';
    (players || []).forEach((p) => {
      if (removeIds.includes(Number(p.booking_id))) return;
      const li = document.createElement('li');
      li.innerHTML = `<span>${p.name || 'Player'}</span>`;
      if ((players || []).length > 1) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'admin-btn admin-btn-ghost admin-btn-sm';
        btn.textContent = 'Remove';
        btn.addEventListener('click', () => {
          removeIds.push(Number(p.booking_id));
          li.remove();
        });
        li.appendChild(btn);
      }
      managePlayersList.appendChild(li);
    });
    addPlayers.forEach((p, idx) => {
      const li = document.createElement('li');
      li.innerHTML = `<span>${p.player_name || 'Player'} <em>(new)</em></span>`;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'admin-btn admin-btn-ghost admin-btn-sm';
      btn.textContent = 'Undo';
      btn.addEventListener('click', () => {
        addPlayers.splice(idx, 1);
        renderPlayers(window.__managePlayersCache || []);
      });
      li.appendChild(btn);
      managePlayersList.appendChild(li);
    });
  };

  document.getElementById('manageAddPlayerBtn')?.addEventListener('click', () => {
    const sel = document.getElementById('manageAddRoster');
    const walkIn = document.getElementById('manageAddWalkIn');
    const walkName = (walkIn?.value || '').trim();
    if (sel?.value) {
      const opt = sel.options[sel.selectedIndex];
      addPlayers.push({ athlete_id: Number(sel.value), player_name: opt.dataset.name || opt.textContent });
      sel.value = '';
      if (walkIn) walkIn.value = '';
    } else if (walkName) {
      addPlayers.push({ player_name: walkName });
      if (walkIn) walkIn.value = '';
    } else {
      return;
    }
    renderPlayers(window.__managePlayersCache || []);
  });

  const loadManage = async (id) => {
    currentBookingId = id;
    currentGroupId = null;
    removeIds = [];
    addPlayers = [];
    manageError.hidden = true;
    const walkIn = document.getElementById('manageAddWalkIn');
    if (walkIn) walkIn.value = '';
    const res = await fetch(`${baseUrl}/${id}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Could not load session.');
    const d = json.data;
    window.__managePlayersCache = d.players || [];
    currentGroupId = d.group_session_id || null;
    document.getElementById('manageNotes').value = d.notes || '';
    document.getElementById('manageDate').value = d.session_date || '';
    document.getElementById('manageTime').value = d.session_time || '';
    document.getElementById('manageDuration').value = d.duration_minutes || 60;
    document.getElementById('manageType').value = d.session_type || 'Private 1-on-1';
    document.getElementById('manageMax').value = d.max_players || 4;
    document.getElementById('manageLocation').value = d.location_id || '';
    document.getElementById('sessionManageSub').textContent = d.capacity_label
      ? `${d.session_type} · ${d.capacity_label}`
      : (d.player_name || 'Session');
    document.getElementById('manageForceDouble').value = '0';
    syncMaxField(manageType, manageMaxField);
    if (managePlayersWrap) managePlayersWrap.hidden = !d.is_group && !isGroupType(d.session_type);
    renderPlayers(d.players || []);
    openModal('sessionManageModal');
  };

  const bindManageOpen = (el) => {
    el.addEventListener('click', (e) => {
      if (didDrag || el.classList.contains('is-drag-origin')) return;
      if (e.target.closest('form, button, a')) return;
      const id = el.dataset.bookingId;
      if (!id) return;
      loadManage(id).catch((err) => alert(err.message || 'Could not open session.'));
    });
  };
  document.querySelectorAll('.sched-event.is-manageable, .sched-agenda-item.is-manageable').forEach(bindManageOpen);

  if (app.dataset.openManage) {
    loadManage(app.dataset.openManage).catch(() => {});
  }

  manageForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentBookingId) return;
    manageError.hidden = true;
    const body = {
      notes: document.getElementById('manageNotes').value,
      session_date: document.getElementById('manageDate').value,
      session_time: document.getElementById('manageTime').value,
      duration_minutes: Number(document.getElementById('manageDuration').value || 60),
      session_type: document.getElementById('manageType').value,
      max_players: Number(document.getElementById('manageMax').value || 4),
      location_id: Number(document.getElementById('manageLocation').value),
      force_double_book: document.getElementById('manageForceDouble').value === '1',
      remove_booking_ids: removeIds,
      add_players: addPlayers,
      view,
      week,
    };
    const res = await fetch(`${baseUrl}/${currentBookingId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-HTTP-Method-Override': 'PATCH',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ ...body, _method: 'PATCH' }),
    });
    const json = await res.json().catch(() => ({}));
    if (res.status === 422 && json.double_book) {
      pendingDoubleAction = () => {
        document.getElementById('manageForceDouble').value = '1';
        manageForm.requestSubmit();
      };
      openModal('doubleBookModal');
      return;
    }
    if (!res.ok) {
      manageError.hidden = false;
      manageErrorText.textContent = json.message || 'Could not save.';
      return;
    }
    applyManageSaveToUi(currentBookingId, json.data || body);
    closeModal('sessionManageModal');
  });

  document.getElementById('manageCancelSession')?.addEventListener('click', async () => {
    if (!currentBookingId) return;
    if (!confirm('Cancel this session? Players will be removed from the calendar.')) return;
    const bookingId = currentBookingId;
    const groupId = currentGroupId;
    const res = await fetch(`${baseUrl}/${bookingId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-HTTP-Method-Override': 'DELETE',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ _method: 'DELETE', view, week }),
    });
    if (!res.ok) {
      const json = await res.json().catch(() => ({}));
      alert(json.message || 'Could not cancel.');
      return;
    }
    removeBookingFromUi(bookingId, groupId);
    closeModal('sessionManageModal');
  });

  // —— Shared calendar UI helpers + smooth pointer drag ——
  const DRAG_THRESHOLD = 5;

  const pad2 = (n) => String(n).padStart(2, '0');

  const formatTimeLabel = (hhmm) => {
    const [hRaw, mRaw] = String(hhmm || '0:0').split(':');
    const h = Number(hRaw);
    const m = Number(mRaw || 0);
    if (Number.isNaN(h)) return '';
    const ampm = h < 12 ? 'AM' : 'PM';
    const h12 = h % 12 || 12;
    return `${h12}:${pad2(m)} ${ampm}`;
  };

  const formatDateLabel = (iso) => {
    const d = new Date(`${iso}T12:00:00`);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
  };

  const gridForTime = (hhmm, durationMins, slotCount) => {
    const [h, m] = String(hhmm).split(':').map(Number);
    const offset = (h * 60 + m) - (calStart * 60);
    const start = Math.max(1, Math.floor(offset / 30) + 1);
    const span = Math.max(2, Math.ceil((durationMins || 60) / 30));
    const rows = slotCount || 34;
    return { start, end: Math.min(rows + 1, start + span) };
  };

  const eventTitleText = (eventEl) => {
    const titleNode = eventEl.querySelector('.sched-event-title');
    if (!titleNode) return '';
    return Array.from(titleNode.childNodes)
      .filter((n) => n.nodeType === Node.TEXT_NODE)
      .map((n) => n.textContent.trim())
      .filter(Boolean)
      .join(' ');
  };

  const sortAgendaList = (list) => {
    if (!list) return;
    const items = Array.from(list.children);
    items.sort((a, b) => {
      const ka = `${a.dataset.date || ''}${a.dataset.time || ''}`;
      const kb = `${b.dataset.date || ''}${b.dataset.time || ''}`;
      return ka.localeCompare(kb);
    });
    items.forEach((item) => list.appendChild(item));
  };

  applyMoveInUi = (bookingId, date, time) => {
    const eventEl = document.querySelector(`.sched-event[data-booking-id="${bookingId}"]`);
    if (!eventEl) return;

    const targetCol = document.querySelector(`.sched-calendar-column[data-date="${date}"]`);
    const duration = Number(eventEl.dataset.duration || 60);
    const timeLabel = formatTimeLabel(time);

    if (targetCol) {
      const slotCount = targetCol.querySelectorAll('.sched-calendar-slot').length;
      const { start, end } = gridForTime(time, duration, slotCount);
      eventEl.style.gridRow = `${start} / ${end}`;
      eventEl.dataset.date = date;
      eventEl.dataset.time = time;
      const timeSpan = eventEl.querySelector('.sched-event-time');
      if (timeSpan) timeSpan.textContent = timeLabel;
      const titleText = eventTitleText(eventEl);
      const typeText = eventEl.querySelector('.sched-event-type')?.textContent || '';
      eventEl.title = [timeLabel, titleText, typeText, duration ? `${duration} min` : ''].filter(Boolean).join(' · ');
      targetCol.appendChild(eventEl);
    } else {
      eventEl.remove();
    }

    eventEl?.classList.remove('is-drag-origin');

    const agendaItem = document.querySelector(`.sched-agenda-item[data-booking-id="${bookingId}"]`);
    if (agendaItem) {
      const whenStrong = agendaItem.querySelector('.sched-agenda-when strong');
      const whenSpan = agendaItem.querySelector('.sched-agenda-when span');
      if (whenStrong) whenStrong.textContent = formatDateLabel(date);
      if (whenSpan) whenSpan.textContent = timeLabel;
      agendaItem.dataset.date = date;
      agendaItem.dataset.time = time;
      sortAgendaList(agendaItem.parentElement);
    }
  };

  removeBookingFromUi = (bookingId, groupId) => {
    if (groupId) {
      document.querySelectorAll(`.sched-event[data-group-id="${groupId}"]`).forEach((el) => el.remove());
    }
    document.querySelector(`.sched-event[data-booking-id="${bookingId}"]`)?.remove();
    document.querySelector(`.sched-agenda-item[data-booking-id="${bookingId}"]`)?.remove();
  };

  applyManageSaveToUi = (bookingId, data) => {
    const date = data.session_date || data.date;
    const time = (data.session_time || data.time || '').slice(0, 5);
    const duration = Number(data.duration_minutes || data.duration || 60);
    const type = data.session_type || '';
    const eventEl = document.querySelector(`.sched-event[data-booking-id="${bookingId}"]`);
    const title = (data.is_group && data.capacity_label)
      ? `${type} · ${data.capacity_label}`
      : (data.player_name || (eventEl ? eventTitleText(eventEl) : '') || 'Session');
    const typeLine = (data.max_players != null && data.players != null)
      ? `${type} · ${data.players}/${data.max_players}`
      : type;

    if (eventEl) {
      eventEl.dataset.duration = String(duration);
      if (data.group_session_id) eventEl.dataset.groupId = data.group_session_id;
      const typeEl = eventEl.querySelector('.sched-event-type');
      if (typeEl && typeLine) typeEl.textContent = typeLine;
      const titleNode = eventEl.querySelector('.sched-event-title');
      if (titleNode && time) {
        titleNode.textContent = '';
        const span = document.createElement('span');
        span.className = 'sched-event-time';
        span.textContent = formatTimeLabel(time);
        titleNode.appendChild(span);
        titleNode.appendChild(document.createTextNode(` ${title}`));
      }
    }

    if (date && time) {
      applyMoveInUi(bookingId, date, time);
    }

    const agendaItem = document.querySelector(`.sched-agenda-item[data-booking-id="${bookingId}"]`);
    if (agendaItem) {
      const nameEl = agendaItem.querySelector('.sched-agenda-name');
      const detailEl = agendaItem.querySelector('.sched-agenda-detail');
      if (nameEl) nameEl.textContent = title;
      if (detailEl) {
        const loc = data.location ? ` · ${data.location}` : '';
        detailEl.textContent = `${type}${duration ? ` · ${duration} min` : ''}${loc}`;
      }
    }
  };

  const hitColumn = (x, y) => {
    const cols = document.querySelectorAll('.sched-calendar-column');
    for (const col of cols) {
      const r = col.getBoundingClientRect();
      if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) {
        return col;
      }
    }
    let best = null;
    let bestDist = Infinity;
    cols.forEach((col) => {
      const r = col.getBoundingClientRect();
      if (y < r.top - 24 || y > r.bottom + 24) return;
      const mid = (r.left + r.right) / 2;
      const dist = Math.abs(x - mid);
      if (dist < bestDist) {
        bestDist = dist;
        best = col;
      }
    });
    return best;
  };

  const slotAtPointer = (col, clientY) => {
    const rect = col.getBoundingClientRect();
    const slots = col.querySelectorAll('.sched-calendar-slot');
    const rowHeight = slots[0]?.offsetHeight || 48;
    const slotCount = slots.length || 34;
    const durationSlots = Math.max(2, Math.ceil(Number(drag.active?.duration || 60) / 30));
    const maxStart = Math.max(0, slotCount - durationSlots);
    const raw = Math.floor((clientY - rect.top) / rowHeight);
    const slotIndex = Math.max(0, Math.min(maxStart, raw));
    const mins = calStart * 60 + slotIndex * 30;
    const time = `${pad2(Math.floor(mins / 60))}:${pad2(mins % 60)}`;
    const { start, end } = gridForTime(time, drag.active?.duration || 60, slotCount);
    return { date: col.dataset.date, time, start, end, slotIndex };
  };

  const drag = {
    active: null,
    floatEl: null,
    snapEl: null,
    dropCol: null,
    lastSnapKey: '',
    raf: 0,
    latestX: 0,
    latestY: 0,
    tracking: false,
    pointerId: null,
    sourceEl: null,
    startX: 0,
    startY: 0,
  };

  const clearDropHighlight = () => {
    if (drag.dropCol) {
      drag.dropCol.classList.remove('is-drop-target');
      drag.dropCol = null;
    }
  };

  const teardownDragChrome = () => {
    if (drag.raf) {
      cancelAnimationFrame(drag.raf);
      drag.raf = 0;
    }
    drag.floatEl?.remove();
    drag.snapEl?.remove();
    drag.floatEl = null;
    drag.snapEl = null;
    drag.lastSnapKey = '';
    clearDropHighlight();
    document.body.classList.remove('sched-is-dragging');
    if (drag.active?.el) {
      drag.active.el.classList.remove('is-drag-origin');
    }
  };

  const placeFloat = (clientX, clientY) => {
    if (!drag.floatEl || !drag.active) return;
    drag.floatEl.style.left = `${clientX - drag.active.offsetX}px`;
    drag.floatEl.style.top = `${clientY - drag.active.offsetY}px`;
  };

  const updateSnap = (clientX, clientY) => {
    const col = hitColumn(clientX, clientY);
    if (!col || !drag.active) {
      if (drag.snapEl) drag.snapEl.hidden = true;
      if (drag.dropCol) {
        drag.dropCol.classList.remove('is-drop-target');
        drag.dropCol = null;
      }
      drag.lastSnapKey = '';
      return null;
    }

    if (drag.dropCol !== col) {
      if (drag.dropCol) drag.dropCol.classList.remove('is-drop-target');
      col.classList.add('is-drop-target');
      drag.dropCol = col;
    }

    const target = slotAtPointer(col, clientY);
    const snapKey = `${target.date}|${target.time}`;
    if (snapKey === drag.lastSnapKey && drag.snapEl && drag.snapEl.parentElement === col) {
      return target;
    }
    drag.lastSnapKey = snapKey;

    if (!drag.snapEl) {
      drag.snapEl = drag.active.el.cloneNode(true);
      drag.snapEl.classList.add('sched-drag-snap');
      drag.snapEl.classList.remove('is-drag-origin', 'is-manageable');
      drag.snapEl.removeAttribute('data-booking-id');
      col.appendChild(drag.snapEl);
    } else if (drag.snapEl.parentElement !== col) {
      col.appendChild(drag.snapEl);
    }
    drag.snapEl.hidden = false;
    drag.snapEl.style.gridRow = `${target.start} / ${target.end}`;
    const timeSpan = drag.snapEl.querySelector('.sched-event-time');
    if (timeSpan) timeSpan.textContent = formatTimeLabel(target.time);
    return target;
  };

  const moveBooking = async (bookingId, date, time, force = false, applyUi = true) => {
    const res = await fetch(`${baseUrl}/${bookingId}/move`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-HTTP-Method-Override': 'PATCH',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        _method: 'PATCH',
        session_date: date,
        session_time: time,
        force_double_book: force,
      }),
    });
    const json = await res.json().catch(() => ({}));
    if (res.status === 422 && json.double_book) {
      pendingDoubleAction = () => moveBooking(bookingId, date, time, true, true);
      openModal('doubleBookModal');
      return false;
    }
    if (!res.ok) {
      alert(json.message || 'Could not move session.');
      return false;
    }
    if (applyUi) {
      applyMoveInUi(bookingId, json.date || date, (json.time || time).slice(0, 5));
    }
    return true;
  };

  const beginDrag = (el, clientX, clientY) => {
    const rect = el.getBoundingClientRect();
    drag.active = {
      el,
      bookingId: el.dataset.bookingId,
      duration: Number(el.dataset.duration || 60),
      originDate: el.dataset.date || '',
      originTime: (el.dataset.time || '').slice(0, 5),
      originParent: el.parentElement,
      originGridRow: el.style.gridRow || getComputedStyle(el).gridRow,
      offsetX: clientX - rect.left,
      offsetY: clientY - rect.top,
    };

    el.classList.add('is-drag-origin');
    document.body.classList.add('sched-is-dragging');
    window.__schedSuppressSlotClickUntil = Date.now() + 800;

    drag.floatEl = el.cloneNode(true);
    drag.floatEl.classList.add('sched-drag-float');
    drag.floatEl.classList.remove('is-drag-origin', 'is-manageable');
    drag.floatEl.style.width = `${rect.width}px`;
    drag.floatEl.style.height = `${rect.height}px`;
    drag.floatEl.style.margin = '0';
    document.body.appendChild(drag.floatEl);
    placeFloat(clientX, clientY);
    updateSnap(clientX, clientY);
    didDrag = true;
  };

  const revertDrag = (finished) => {
    if (!finished) return;
    applyMoveInUi(finished.bookingId, finished.originDate, finished.originTime);
    if (finished.originParent && finished.originGridRow) {
      finished.el.style.gridRow = finished.originGridRow;
      finished.originParent.appendChild(finished.el);
    }
    finished.el.classList.remove('is-drag-origin');
  };

  const endDrag = async (clientX, clientY) => {
    if (!drag.active) return;
    const finished = drag.active;
    const { bookingId, originDate, originTime, el } = finished;
    const target = updateSnap(clientX, clientY);
    teardownDragChrome();
    drag.active = null;
    window.__schedSuppressSlotClickUntil = Date.now() + 500;

    if (!target || !target.date || (target.date === originDate && target.time === originTime)) {
      el.classList.remove('is-drag-origin');
      return;
    }

    applyMoveInUi(bookingId, target.date, target.time);
    const ok = await moveBooking(bookingId, target.date, target.time, false, false);
    if (!ok) {
      revertDrag(finished);
    }
  };

  const paintDrag = () => {
    drag.raf = 0;
    if (!drag.active) return;
    placeFloat(drag.latestX, drag.latestY);
    updateSnap(drag.latestX, drag.latestY);
  };

  const onDocPointerMove = (e) => {
    if (!drag.tracking || e.pointerId !== drag.pointerId) return;
    if (!drag.active) {
      const dx = e.clientX - drag.startX;
      const dy = e.clientY - drag.startY;
      if (Math.hypot(dx, dy) < DRAG_THRESHOLD) return;
      beginDrag(drag.sourceEl, e.clientX, e.clientY);
    }
    if (drag.active) {
      e.preventDefault();
      drag.latestX = e.clientX;
      drag.latestY = e.clientY;
      if (!drag.raf) drag.raf = requestAnimationFrame(paintDrag);
    }
  };

  const onDocPointerUp = async (e) => {
    if (!drag.tracking || e.pointerId !== drag.pointerId) return;
    drag.tracking = false;
    document.removeEventListener('pointermove', onDocPointerMove);
    document.removeEventListener('pointerup', onDocPointerUp);
    document.removeEventListener('pointercancel', onDocPointerCancel);
    try { drag.sourceEl?.releasePointerCapture(drag.pointerId); } catch (_) { /* ignore */ }
    if (drag.active) {
      await endDrag(e.clientX, e.clientY);
    }
    setTimeout(() => { didDrag = false; }, 0);
  };

  const onDocPointerCancel = () => {
    if (!drag.tracking) return;
    drag.tracking = false;
    document.removeEventListener('pointermove', onDocPointerMove);
    document.removeEventListener('pointerup', onDocPointerUp);
    document.removeEventListener('pointercancel', onDocPointerCancel);
    teardownDragChrome();
    if (drag.active?.el) drag.active.el.classList.remove('is-drag-origin');
    drag.active = null;
    didDrag = false;
  };

  document.querySelectorAll('.sched-event.is-manageable').forEach((el) => {
    el.addEventListener('pointerdown', (e) => {
      if (e.button !== 0) return;
      if (e.target.closest('form, button, a')) return;
      if (drag.tracking || drag.active) return;
      drag.tracking = true;
      drag.pointerId = e.pointerId;
      drag.sourceEl = el;
      drag.startX = e.clientX;
      drag.startY = e.clientY;
      didDrag = false;
      try { el.setPointerCapture(e.pointerId); } catch (_) { /* ignore */ }
      document.addEventListener('pointermove', onDocPointerMove, { passive: false });
      document.addEventListener('pointerup', onDocPointerUp);
      document.addEventListener('pointercancel', onDocPointerCancel);
    });
  });
})();
