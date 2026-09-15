(() => {
  const busyMarkup = (label) =>
    `<span class="cn-btn-busy"><span class="cn-btn-spinner" aria-hidden="true"></span><span class="cn-btn-busy-label">${label}</span></span>`;

  const isMutatingForm = (form) => {
    const method = String(form.getAttribute('method') || 'get').toLowerCase();
    if (method === 'post') return true;
    const override = form.querySelector('input[name="_method"]')?.value;
    return ['post', 'put', 'patch', 'delete'].includes(String(override || '').toLowerCase());
  };

  const submitControls = (form) =>
    Array.from(form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]'));

  const readableLabel = (el) => {
    const custom = el.getAttribute('data-loading-text');
    if (custom) return custom;
    const text = (el.textContent || el.value || '').replace(/\s+/g, ' ').trim();
    return text || 'Please wait…';
  };

  const setBusy = (el, options = {}) => {
    if (!el || el.dataset.cnBusy === '1') return;

    const label = options.label || readableLabel(el);
    el.dataset.cnBusy = '1';
    el.dataset.cnBusyLabel = el.tagName === 'INPUT' ? el.value : el.innerHTML;
    el.style.minWidth = `${Math.ceil(el.getBoundingClientRect().width)}px`;
    el.classList.add('is-busy');
    el.setAttribute('aria-busy', 'true');
    if ('disabled' in el) el.disabled = true;

    if (el.tagName === 'INPUT') {
      el.value = label;
    } else {
      el.innerHTML = busyMarkup(label);
    }
  };

  const clearBusy = (el) => {
    if (!el || el.dataset.cnBusy !== '1') return;
    const original = el.dataset.cnBusyLabel || '';
    delete el.dataset.cnBusy;
    delete el.dataset.cnBusyLabel;
    el.classList.remove('is-busy');
    el.removeAttribute('aria-busy');
    el.style.minWidth = '';
    if ('disabled' in el) el.disabled = false;
    if (el.tagName === 'INPUT') {
      el.value = original;
    } else {
      el.innerHTML = original;
    }
  };

  const setFormBusy = (form, options = {}) => {
    if (!form || form.dataset.cnBusy === '1') return false;
    form.dataset.cnBusy = '1';
    form.classList.add('is-submitting');
    submitControls(form).forEach((btn) => {
      if (btn.disabled && !btn.dataset.cnBusy) {
        btn.dataset.cnSkipBusy = '1';
        return;
      }
      setBusy(btn, options);
    });
    return true;
  };

  const clearFormBusy = (form) => {
    if (!form) return;
    delete form.dataset.cnBusy;
    form.classList.remove('is-submitting');
    submitControls(form).forEach((btn) => {
      if (btn.dataset.cnSkipBusy === '1') {
        delete btn.dataset.cnSkipBusy;
        return;
      }
      clearBusy(btn);
    });
  };

  document.addEventListener(
    'submit',
    (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (form.hasAttribute('data-auto-filter')) return;
      if (form.hasAttribute('data-no-busy')) return;
      if (!isMutatingForm(form)) return;

      if (form.dataset.cnBusy === '1') {
        event.preventDefault();
        return;
      }

      const submitter = event.submitter;
      if (submitter?.disabled) {
        event.preventDefault();
        return;
      }

      const label =
        submitter?.getAttribute('data-loading-text') ||
        form.getAttribute('data-loading-text') ||
        (submitter ? readableLabel(submitter) : null);

      setFormBusy(form, label ? { label } : {});

      window.setTimeout(() => {
        if (document.body.contains(form) && form.matches(':invalid')) {
          clearFormBusy(form);
        }
      }, 0);
    },
    true
  );

  window.CoachNowBusy = { setBusy, clearBusy, setFormBusy, clearFormBusy };
})();
