(() => {
  const root = document.querySelector('[data-player-notify]');
  if (!root) return;

  const btn = document.getElementById('playerNotifyBtn');
  const panel = document.getElementById('playerNotifyPanel');
  const badge = root.querySelector('[data-notify-badge]');
  const markBtn = root.querySelector('[data-notify-mark]');

  if (!btn || !panel) return;

  const unreadCount = () => root.querySelectorAll('.player-notify__item.is-unread').length;

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

  const setOpen = (open) => {
    root.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.hidden = !open;
  };

  btn.addEventListener('click', (event) => {
    event.stopPropagation();
    setOpen(panel.hidden);
  });

  markBtn?.addEventListener('click', (event) => {
    event.stopPropagation();
    root.querySelectorAll('.player-notify__item.is-unread').forEach((item) => {
      item.classList.remove('is-unread');
      const dot = item.querySelector('.player-notify__dot');
      if (dot) dot.classList.add('is-muted');
    });
    syncBadge();
  });

  document.addEventListener('click', (event) => {
    if (!root.contains(event.target)) {
      setOpen(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
  });

  syncBadge();
})();
