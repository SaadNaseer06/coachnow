(() => {
  if (window.CoachNowDialog) return;

  let root = null;
  let resolvePromise = null;

  function ensureRoot() {
    if (root) return root;

    root = document.createElement('div');
    root.className = 'cn-dialog';
    root.hidden = true;
    root.setAttribute('data-lenis-prevent', '');
    root.innerHTML = `
      <div class="cn-dialog__backdrop" data-cn-dialog-dismiss></div>
      <div class="cn-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="cnDialogTitle">
        <p class="cn-dialog__eyebrow" data-cn-dialog-eyebrow>CoachNow</p>
        <h2 class="cn-dialog__title" id="cnDialogTitle" data-cn-dialog-title>Notice</h2>
        <p class="cn-dialog__message" data-cn-dialog-message></p>
        <div class="cn-dialog__actions">
          <button type="button" class="cn-dialog__btn cn-dialog__btn--ghost" data-cn-dialog-cancel hidden>Cancel</button>
          <button type="button" class="cn-dialog__btn cn-dialog__btn--primary" data-cn-dialog-confirm>OK</button>
        </div>
      </div>
    `;
    document.body.appendChild(root);

    root.querySelector('[data-cn-dialog-confirm]')?.addEventListener('click', () => close(true));
    root.querySelector('[data-cn-dialog-cancel]')?.addEventListener('click', () => close(false));
    root.querySelector('[data-cn-dialog-dismiss]')?.addEventListener('click', () => {
      const cancel = root.querySelector('[data-cn-dialog-cancel]');
      close(cancel && !cancel.hidden ? false : true);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && root && !root.hidden) {
        const cancel = root.querySelector('[data-cn-dialog-cancel]');
        close(cancel && !cancel.hidden ? false : true);
      }
    });

    return root;
  }

  function close(result) {
    if (!root || root.hidden) return;
    root.hidden = true;
    document.body.classList.remove('cn-dialog-open');
    window.coachNowLenis?.start?.();
    const resolve = resolvePromise;
    resolvePromise = null;
    if (resolve) resolve(result);
  }

  function open({
    title = 'Notice',
    message = '',
    eyebrow = 'CoachNow',
    confirmLabel = 'OK',
    cancelLabel = null,
  } = {}) {
    const el = ensureRoot();
    el.querySelector('[data-cn-dialog-eyebrow]').textContent = eyebrow;
    el.querySelector('[data-cn-dialog-title]').textContent = title;
    el.querySelector('[data-cn-dialog-message]').textContent = message;

    const confirmBtn = el.querySelector('[data-cn-dialog-confirm]');
    const cancelBtn = el.querySelector('[data-cn-dialog-cancel]');
    confirmBtn.textContent = confirmLabel;

    if (cancelLabel) {
      cancelBtn.hidden = false;
      cancelBtn.textContent = cancelLabel;
    } else {
      cancelBtn.hidden = true;
    }

    el.hidden = false;
    document.body.classList.add('cn-dialog-open');
    window.coachNowLenis?.stop?.();
    confirmBtn.focus();

    return new Promise((resolve) => {
      resolvePromise = resolve;
    });
  }

  window.CoachNowDialog = {
    alert(options) {
      if (typeof options === 'string') {
        return open({ title: 'Notice', message: options, confirmLabel: 'Got it' });
      }
      return open({
        title: options?.title || 'Notice',
        message: options?.message || '',
        eyebrow: options?.eyebrow || 'CoachNow',
        confirmLabel: options?.confirmLabel || 'Got it',
      });
    },
    confirm(options) {
      if (typeof options === 'string') {
        return open({
          title: 'Please confirm',
          message: options,
          confirmLabel: 'Confirm',
          cancelLabel: 'Cancel',
        });
      }
      return open({
        title: options?.title || 'Please confirm',
        message: options?.message || '',
        eyebrow: options?.eyebrow || 'CoachNow',
        confirmLabel: options?.confirmLabel || 'Confirm',
        cancelLabel: options?.cancelLabel || 'Cancel',
      });
    },
  };
})();
