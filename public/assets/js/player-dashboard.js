(() => {
  const root = document.querySelector('[data-player-notify]');
  if (!root) return;

  const btn = document.getElementById('playerNotifyBtn');
  const panel = document.getElementById('playerNotifyPanel');
  const badge = root.querySelector('[data-notify-badge]');
  const markBtn = root.querySelector('[data-notify-mark]');

  if (!btn || !panel) return;

  /* Escape hero overflow / transform so the panel can overlap page content */
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
})();
