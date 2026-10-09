(() => {
  function mount(root) {
    if (!root || root.dataset.paymentReady === '1') return root;
    root.dataset.paymentReady = '1';

    const listEl = root.querySelector('[data-payment-list]');
    const errorEl = root.querySelector('[data-payment-error]');
    const stripeMount = root.querySelector('[data-stripe-mount]');
    const modeLabel = root.querySelector('[data-payment-mode-label]');
    const fallbackEl = root.querySelector('[data-payment-fallback]');
    let stripeSaver = null;

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

    async function enableStripeMode() {
      if (!window.CoachNowStripe || !stripeMount) return false;
      try {
        const enabled = await window.CoachNowStripe.enabled();
        if (!enabled) {
          if (fallbackEl) fallbackEl.hidden = false;
          if (modeLabel) modeLabel.textContent = 'Unavailable';
          return false;
        }
        if (modeLabel) modeLabel.textContent = 'Stripe';
        if (listEl) listEl.hidden = true;
        if (fallbackEl) fallbackEl.hidden = true;
        stripeMount.hidden = false;
        stripeSaver = await window.CoachNowStripe.saveCard(stripeMount);
        return true;
      } catch (err) {
        showError(err.message || 'Could not load secure checkout.');
        if (fallbackEl) fallbackEl.hidden = false;
        return false;
      }
    }

    root._coachNowPayment = {
      showError,
      collapseForm() {
        showError('');
      },
      selected() {
        return null;
      },
      charge() {
        showError('Secure checkout is required. Refresh and try again.');
        return { ok: false, error: 'stripe_required' };
      },
      async authorize() {
        showError('');
        if (!stripeSaver) {
          await enableStripeMode();
        }
        if (!stripeSaver) {
          showError('Secure checkout is not available right now. Please try again shortly.');
          return { ok: false, error: 'stripe_unavailable' };
        }
        try {
          const saved = await stripeSaver.confirm();
          return {
            ok: true,
            method: {
              brand: 'Card',
              last4: '••••',
              label: saved.label,
            },
            stripe_payment_method_id: saved.payment_method_id,
          };
        } catch (err) {
          showError(err.message || 'Could not save card.');
          return { ok: false, error: err.message };
        }
      },
      prepareStripe: enableStripeMode,
    };

    enableStripeMode();
    return root;
  }

  function api(root) {
    if (!root) return null;
    mount(root);
    return root._coachNowPayment;
  }

  window.CoachNowPayment = { mount, api };
})();
