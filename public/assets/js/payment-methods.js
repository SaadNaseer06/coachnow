(() => {
  const STORAGE_KEY = 'coachnow_payment_methods';

  const TEST_CARDS = {
    '4242424242424242': { brand: 'Visa', result: 'success' },
    '4000000000000002': { brand: 'Visa', result: 'declined' },
    '5555555555554444': { brand: 'Mastercard', result: 'success' },
    '378282246310005': { brand: 'American Express', result: 'success' },
  };

  const DEFAULT_METHODS = [
    { id: 'pm_visa_4242', brand: 'Visa', last4: '4242', exp: '12/28', name: 'Jamie Underwood' },
    { id: 'pm_mc_4444', brand: 'Mastercard', last4: '4444', exp: '09/27', name: 'Jamie Underwood' },
  ];

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function digits(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function brandFromPan(pan) {
    if (/^3[47]/.test(pan)) return 'American Express';
    if (/^5[1-5]/.test(pan) || /^2(2|7)/.test(pan)) return 'Mastercard';
    if (/^4/.test(pan)) return 'Visa';
    return 'Card';
  }

  function formatPan(value) {
    const num = digits(value).slice(0, 16);
    return num.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
  }

  function formatExp(value) {
    const num = digits(value).slice(0, 4);
    if (num.length <= 2) return num;
    return `${num.slice(0, 2)} / ${num.slice(2)}`;
  }

  function loadMethods() {
    try {
      const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
      if (Array.isArray(stored) && stored.length) return stored;
    } catch {
      /* fall through */
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_METHODS));
    return [...DEFAULT_METHODS];
  }

  function saveMethods(methods) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(methods));
  }

  function luhnOk(pan) {
    let sum = 0;
    let alt = false;
    for (let i = pan.length - 1; i >= 0; i -= 1) {
      let n = Number(pan[i]);
      if (alt) {
        n *= 2;
        if (n > 9) n -= 9;
      }
      sum += n;
      alt = !alt;
    }
    return sum % 10 === 0;
  }

  function chargeResult(method, pan) {
    const number = pan || method?.pan || '';
    const known = TEST_CARDS[number];
    if (known) return known.result;
    if (method?.last4 === '0002') return 'declined';
    if (number && (number.length < 15 || !luhnOk(number))) return 'invalid';
    return 'success';
  }

  function mount(root) {
    if (!root || root.dataset.paymentReady === '1') return root;
    root.dataset.paymentReady = '1';

    const listEl = root.querySelector('[data-payment-list]');
    const form = root.querySelector('[data-payment-form]');
    const toggle = root.querySelector('[data-payment-add-toggle]');
    const saveBtn = root.querySelector('[data-payment-save]');
    const errorEl = root.querySelector('[data-payment-error]');
    const numberInput = root.querySelector('[data-card-number]');
    const expInput = root.querySelector('[data-card-exp]');
    const cvcInput = root.querySelector('[data-card-cvc]');
    const nameInput = root.querySelector('[data-card-name]');

    function showError(message) {
      if (!errorEl) return;
      if (!message) {
        errorEl.hidden = true;
        errorEl.textContent = '';
        return;
      }
      errorEl.hidden = false;
      errorEl.textContent = message;
    }

    function selectedId() {
      return root.querySelector('input[name="' + root.dataset.paymentRoot + '-method"]:checked')?.value || '';
    }

    function render() {
      const methods = loadMethods();
      const current = selectedId();
      const pick = current && methods.some((m) => m.id === current) ? current : methods[0]?.id;

      listEl.innerHTML = methods.map((method) => `
        <label class="pay-method ${pick === method.id ? 'is-selected' : ''}">
          <input type="radio" name="${escapeHtml(root.dataset.paymentRoot)}-method" value="${escapeHtml(method.id)}" ${pick === method.id ? 'checked' : ''}>
          <span class="pay-method__brand">${escapeHtml(method.brand)}</span>
          <span class="pay-method__meta">
            <strong>${escapeHtml(method.brand)} ···· ${escapeHtml(method.last4)}</strong>
            <em>Expires ${escapeHtml(method.exp)}${method.name ? ' · ' + escapeHtml(method.name) : ''}</em>
          </span>
        </label>
      `).join('');

      listEl.querySelectorAll('input[type="radio"]').forEach((input) => {
        input.addEventListener('change', () => {
          showError('');
          listEl.querySelectorAll('.pay-method').forEach((row) => {
            row.classList.toggle('is-selected', row.querySelector('input')?.checked);
          });
          if (form) form.hidden = true;
        });
      });
    }

    function readNewCard() {
      const pan = digits(numberInput?.value);
      const exp = formatExp(expInput?.value).replace(/\s/g, '');
      const cvc = digits(cvcInput?.value);
      const name = nameInput?.value.trim() || 'Cardholder';
      const amex = /^3[47]/.test(pan);
      const validLen = amex ? pan.length === 15 : pan.length === 16;
      const validCvc = amex ? cvc.length === 4 : cvc.length === 3;
      const validExp = /^\d{2}\/\d{2}$/.test(exp);

      if (!validLen || !luhnOk(pan)) return { error: 'Enter a valid test card number.' };
      if (!validExp) return { error: 'Enter expiry as MM / YY.' };
      if (!validCvc) return { error: 'Enter a valid CVC.' };

      return {
        method: {
          id: `pm_${Date.now()}`,
          brand: brandFromPan(pan),
          last4: pan.slice(-4),
          exp,
          name,
        },
        pan,
      };
    }

    toggle?.addEventListener('click', () => {
      if (!form) return;
      form.hidden = !form.hidden;
      showError('');
      if (!form.hidden) nameInput?.focus();
    });

    numberInput?.addEventListener('input', () => {
      numberInput.value = formatPan(numberInput.value);
    });
    expInput?.addEventListener('input', () => {
      expInput.value = formatExp(expInput.value);
    });
    cvcInput?.addEventListener('input', () => {
      cvcInput.value = digits(cvcInput.value).slice(0, 4);
    });

    root.querySelectorAll('[data-fill-card]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const pan = btn.dataset.fillCard || '';
        if (numberInput) numberInput.value = formatPan(pan);
        if (expInput) expInput.value = '12 / 28';
        if (cvcInput) cvcInput.value = pan.startsWith('37') ? '1234' : '123';
        if (nameInput && !nameInput.value) nameInput.value = 'Jamie Underwood';
        if (form) form.hidden = false;
        showError('');
      });
    });

    saveBtn?.addEventListener('click', () => {
      const result = readNewCard();
      if (result.error) {
        showError(result.error);
        return;
      }
      const methods = loadMethods();
      methods.unshift(result.method);
      saveMethods(methods);
      if (form) form.hidden = true;
      if (numberInput) numberInput.value = '';
      if (expInput) expInput.value = '';
      if (cvcInput) cvcInput.value = '';
      showError('');
      render();
      const radio = listEl.querySelector(`input[value="${result.method.id}"]`);
      if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
      }
    });

    root._coachNowPayment = {
      showError,
      collapseForm() {
        if (form) form.hidden = true;
        showError('');
      },
      selected() {
        const methods = loadMethods();
        return methods.find((m) => m.id === selectedId()) || null;
      },
      charge() {
        showError('');
        if (form && !form.hidden && digits(numberInput?.value)) {
          const created = readNewCard();
          if (created.error) {
            showError(created.error);
            return { ok: false, error: created.error };
          }
          const methods = loadMethods();
          methods.unshift(created.method);
          saveMethods(methods);
          render();
          const outcome = chargeResult(created.method, created.pan);
          if (outcome === 'declined') {
            showError('This test card was declined. Try 4242 4242 4242 4242.');
            return { ok: false, error: 'declined' };
          }
          if (outcome === 'invalid') {
            showError('That card number is not valid.');
            return { ok: false, error: 'invalid' };
          }
          return { ok: true, method: created.method };
        }

        const method = this.selected();
        if (!method) {
          showError('Select a payment method or add a new card.');
          return { ok: false, error: 'none' };
        }
        const outcome = chargeResult(method);
        if (outcome === 'declined') {
          showError('This test card was declined. Choose another method or add 4242 4242 4242 4242.');
          return { ok: false, error: 'declined' };
        }
        return { ok: true, method };
      },
      authorize() {
        return this.charge();
      },
    };

    render();
    return root;
  }

  function api(root) {
    if (!root) return null;
    mount(root);
    return root._coachNowPayment;
  }

  window.CoachNowPayment = { mount, api };
})();
