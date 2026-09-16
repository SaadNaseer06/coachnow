(() => {
  const root = document.querySelector('[data-player-notify]');
  if (root) {
    const btn = document.getElementById('playerNotifyBtn');
    const panel = document.getElementById('playerNotifyPanel');
    const badge = root.querySelector('[data-notify-badge]');
    const markBtn = root.querySelector('[data-notify-mark]');

    if (btn && panel) {
      document.body.appendChild(panel);

      const unreadCount = () => panel.querySelectorAll('.player-notify__item.is-unread').length;

      const syncBadge = () => {
        const count = unreadCount();
        if (!badge) return;

        if (count > 0) {
          badge.hidden = false;
          badge.textContent = String(count);
        } else {
          badge.hidden = true;
        }
      };

      const placePanel = () => {
        const rect = btn.getBoundingClientRect();
        const gap = 10;
        const width = Math.min(360, window.innerWidth - 48);
        let left = rect.right - width;
        left = Math.max(24, Math.min(left, window.innerWidth - width - 24));

        panel.style.width = `${width}px`;
        panel.style.left = `${left}px`;
        panel.style.right = 'auto';
        panel.style.top = `${rect.bottom + gap}px`;
      };

      const setOpen = (open) => {
        root.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.hidden = !open;

        if (open) {
          placePanel();
        }
      };

      btn.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(panel.hidden);
      });

      markBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        panel.querySelectorAll('.player-notify__item.is-unread').forEach((item) => {
          item.classList.remove('is-unread');
          const dot = item.querySelector('.player-notify__dot');
          if (dot) dot.classList.add('is-muted');
        });
        syncBadge();
      });

      panel.addEventListener('click', (event) => {
        event.stopPropagation();
      });

      document.addEventListener('click', () => {
        setOpen(false);
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
      });

      window.addEventListener(
        'resize',
        () => {
          if (!panel.hidden) placePanel();
        },
        { passive: true }
      );

      window.addEventListener(
        'scroll',
        () => {
          if (!panel.hidden) placePanel();
        },
        { passive: true, capture: true }
      );

      syncBadge();
    }
  }

  const requestsRoot = document.querySelector('[data-player-requests]');
  if (!requestsRoot) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  requestsRoot.addEventListener('click', async (event) => {
    const btn = event.target.closest('[data-cancel-request]');
    if (!btn) return;

    const reference = btn.getAttribute('data-reference');
    if (!reference) return;

    const confirmed = window.CoachNowDialog?.confirm
      ? await window.CoachNowDialog.confirm({
          title: 'Cancel request?',
          message: 'Cancel this session request? Coaches will no longer see it.',
          confirmLabel: 'Cancel request',
          cancelLabel: 'Keep request',
        })
      : window.confirm('Cancel this session request? Coaches will no longer see it.');

    if (!confirmed) {
      return;
    }

    btn.disabled = true;
    window.CoachNowBusy?.setBusy(btn, { label: 'Cancelling…' });

    try {
      const res = await fetch(`/api/session-requests/${encodeURIComponent(reference)}/cancel`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const payload = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(payload.message || 'Could not cancel this request.');
      }

      const card = btn.closest('[data-request-card]');
      if (!card) return;

      card.classList.add('is-cancelled');
      card.dataset.status = 'cancelled';

      const badgeEl = card.querySelector('[data-request-status]');
      if (badgeEl) {
        badgeEl.textContent = 'Cancelled';
        badgeEl.className = 'player-req-badge player-req-badge--zinc';
      }

      const sub = card.querySelector('.player-req-card__sub');
      if (sub) {
        sub.textContent = `${(sub.textContent || '').replace(/\s·\sWaiting for a coach/, '')} · Cancelled`;
      }

      btn.remove();
    } catch (error) {
      window.CoachNowBusy?.clearBusy(btn);
      btn.disabled = false;
      btn.textContent = 'Cancel';
      if (window.CoachNowDialog?.alert) {
        await window.CoachNowDialog.alert({
          title: 'Cancel failed',
          message: error.message || 'Could not cancel this request.',
        });
      } else {
        window.alert(error.message || 'Could not cancel this request.');
      }
    }
  });
})();
