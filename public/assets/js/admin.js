(() => {
  const menuBtn = document.getElementById('adminMenuBtn');
  const backdrop = document.getElementById('adminSidebarBackdrop');

  const closeSidebar = () => document.body.classList.remove('admin-sidebar-open');
  const toggleSidebar = () => document.body.classList.toggle('admin-sidebar-open');

  if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  const openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('admin-modal-open');
    const focusable = modal.querySelector('input, select, textarea, button');
    if (focusable) focusable.focus();
  };

  const closeModal = (modal) => {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.admin-modal.is-open')) {
      document.body.classList.remove('admin-modal-open');
    }
  };

  document.querySelectorAll('[data-admin-modal-open]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const modalId = btn.getAttribute('data-admin-modal-open');
      if (modalId === 'deleteLocationModal') {
        const form = document.getElementById('deleteLocationForm');
        const nameEl = document.getElementById('deleteLocationName');
        if (form) form.setAttribute('action', btn.getAttribute('data-delete-url') || '');
        if (nameEl) nameEl.textContent = btn.getAttribute('data-delete-name') || 'this location';
      }
      openModal(modalId);
    });
  });

  document.querySelectorAll('[data-admin-modal-close]').forEach((el) => {
    el.addEventListener('click', () => closeModal(el.closest('.admin-modal')));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeSidebar();
    document.querySelectorAll('.admin-modal.is-open').forEach((modal) => closeModal(modal));
  });

  // Keep validation-reopened modals locked from body scroll
  if (document.querySelector('.admin-modal.is-open')) {
    document.body.classList.add('admin-modal-open');
  }
})();

(() => {
  document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
    let timer = null;

    const submitFilters = () => {
      const params = new URLSearchParams(new FormData(form));
      [...params.entries()].forEach(([key, value]) => {
        if (String(value).trim() === '') params.delete(key);
      });
      const query = params.toString();
      window.location.href = query ? `${form.action}?${query}` : form.action;
    };

    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', submitFilters);
    });

    form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => {
      input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(submitFilters, 350);
      });
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          clearTimeout(timer);
          submitFilters();
        }
      });
    });
  });
})();

// Share an open group session link: native share sheet on phones (Instagram, Facebook,
// Messages…), copy-to-clipboard on desktop.
(() => {
  document.addEventListener('click', async (event) => {
    const btn = event.target.closest('[data-share-group]');
    if (!btn) return;
    event.preventDefault();

    const url = btn.dataset.shareUrl || document.getElementById(btn.dataset.shareInput || '')?.value || '';
    if (!url) return;
    const title = btn.dataset.shareTitle || 'Group session on CoachNow';
    const text = btn.dataset.shareText || 'Spots open — join on CoachNow';

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url });
        return;
      } catch (err) {
        if (err && err.name === 'AbortError') return;
      }
    }

    const label = btn.textContent;
    try {
      await navigator.clipboard.writeText(url);
      btn.textContent = 'Link copied';
    } catch {
      window.prompt('Copy this link to share:', url);
    }
    setTimeout(() => { btn.textContent = label; }, 1600);
  });
})();
