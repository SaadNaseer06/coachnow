(() => {
  const menuBtn = document.getElementById('coachMenuBtn');
  const backdrop = document.getElementById('coachSidebarBackdrop');

  const closeSidebar = () => document.body.classList.remove('admin-sidebar-open');
  const toggleSidebar = () => document.body.classList.toggle('admin-sidebar-open');

  if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeSidebar();
  });

  const coachId = window.CoachNowCoachId != null ? Number(window.CoachNowCoachId) : null;
  let lastStatus = window.CoachNowCoachStatus
    || document.querySelector('[data-coach-status-badge]')?.dataset.status
    || document.querySelector('[data-coach-status-banner]')?.dataset.status
    || null;

  function badgeClassFor(status) {
    if (status === 'active') return 'admin-badge admin-badge-green';
    if (status === 'pending') return 'admin-badge admin-badge-amber';
    return 'admin-badge admin-badge-zinc';
  }

  function bannerClassFor(status) {
    return status === 'active'
      ? 'admin-alert admin-alert--success'
      : 'admin-alert admin-alert--error';
  }

  function ensureToastHost() {
    let host = document.getElementById('coachStatusToastHost');
    if (host) return host;
    host = document.createElement('div');
    host.id = 'coachStatusToastHost';
    host.className = 'coach-status-toast-host';
    host.setAttribute('aria-live', 'polite');
    document.body.appendChild(host);
    return host;
  }

  function showStatusToast(payload) {
    const host = ensureToastHost();
    const toast = document.createElement('div');
    toast.className = `coach-status-toast coach-status-toast--${payload.status || 'pending'}`;
    toast.innerHTML = `
      <div class="coach-status-toast__body">
        <p class="coach-status-toast__eyebrow">Listing update</p>
        <p class="coach-status-toast__title">${escapeHtml(payload.title || 'Status updated')}</p>
        <p class="coach-status-toast__message">${escapeHtml(payload.message || '')}</p>
      </div>
      <div class="coach-status-toast__actions">
        <a class="coach-status-toast__link" href="${escapeHtml(payload.profile_url || '/coach/profile')}">View profile</a>
        <button type="button" class="coach-status-toast__close" aria-label="Dismiss">×</button>
      </div>
    `;
    host.appendChild(toast);
    toast.querySelector('.coach-status-toast__close')?.addEventListener('click', () => toast.remove());
    window.setTimeout(() => toast.remove(), 12000);
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function applyStatusToUi(payload, { notify = true } = {}) {
    const status = payload?.status;
    if (!status) return;

    const changed = lastStatus && lastStatus !== status;
    lastStatus = status;
    window.CoachNowCoachStatus = status;

    document.querySelectorAll('[data-coach-status-badge]').forEach((badge) => {
      badge.dataset.status = status;
      badge.className = badgeClassFor(status);
      badge.textContent = payload.label || (status.charAt(0).toUpperCase() + status.slice(1));
    });

    document.querySelectorAll('[data-coach-status-banner]').forEach((banner) => {
      banner.hidden = false;
      banner.dataset.status = status;
      banner.className = bannerClassFor(status);
      const label = banner.querySelector('[data-coach-status-banner-label]');
      const text = banner.querySelector('[data-coach-status-banner-text]');
      if (label) label.textContent = payload.title || 'Listing status updated';
      if (text) {
        const profileUrl = payload.profile_url || '/coach/profile';
        text.innerHTML = `${escapeHtml(payload.message || '')} <a href="${escapeHtml(profileUrl)}" class="font-semibold underline" style="color:inherit;">Open My Profile</a>`;
      }
    });

    if (notify && changed) {
      showStatusToast(payload);
    }
  }

  async function fetchStatus({ notifyOnChange = true } = {}) {
    try {
      const response = await fetch('/coach/api/status', {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      if (!response.ok) return;
      const payload = await response.json();
      applyStatusToUi(payload, { notify: notifyOnChange });
    } catch {
      /* ignore transient network errors */
    }
  }

  if (coachId && window.CoachNowRealtime?.enabled && window.Echo) {
    window.Echo.private(`coach.status.${coachId}`)
      .listen('.coach.status.changed', (payload) => {
        applyStatusToUi(payload, { notify: true });
      });
  }

  // Safety net when websockets drop or admin action races the page load.
  window.setInterval(() => fetchStatus({ notifyOnChange: true }), 20000);
})();
