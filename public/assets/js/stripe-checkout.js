(() => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  async function api(url, options = {}) {
    const res = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.message || 'Request failed');
    }
    return data;
  }

  let cachedConfig = null;
  let stripePromise = null;

  async function loadConfig() {
    if (cachedConfig) return cachedConfig;
    cachedConfig = await api('/api/stripe/config');
    return cachedConfig;
  }

  function loadStripeJs(publishableKey) {
    if (window.Stripe) {
      return Promise.resolve(window.Stripe(publishableKey));
    }
    if (stripePromise) return stripePromise;
    stripePromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://js.stripe.com/v3/';
      script.onload = () => {
        if (!window.Stripe) {
          reject(new Error('Stripe.js failed to load'));
          return;
        }
        resolve(window.Stripe(publishableKey));
      };
      script.onerror = () => reject(new Error('Stripe.js failed to load'));
      document.head.appendChild(script);
    });
    return stripePromise;
  }

  async function getStripe() {
    const config = await loadConfig();
    if (!config.enabled || !config.publishable_key) {
      return { enabled: false, config };
    }
    const stripe = await loadStripeJs(config.publishable_key);
    return { enabled: true, stripe, config };
  }

  /**
   * Save a card via SetupIntent. Returns { payment_method_id, label }.
   */
  async function saveCard(mountEl) {
    const { enabled, stripe, config } = await getStripe();
    if (!enabled) {
      throw new Error('Stripe is not configured.');
    }

    const setup = await api('/api/stripe/setup-intent');
    const elements = stripe.elements({
      clientSecret: setup.data.client_secret,
      appearance: { theme: 'stripe' },
    });
    const paymentElement = elements.create('payment');
    mountEl.innerHTML = '';
    paymentElement.mount(mountEl);

    return {
      elements,
      paymentElement,
      async confirm() {
        const result = await stripe.confirmSetup({
          elements,
          redirect: 'if_required',
          confirmParams: {
            return_url: window.location.href,
          },
        });
        if (result.error) {
          throw new Error(result.error.message || 'Could not save card.');
        }
        const pm = result.setupIntent?.payment_method;
        const id = typeof pm === 'string' ? pm : pm?.id;
        return {
          payment_method_id: id,
          label: 'Card on file',
          deposit_amount: config.deposit_amount,
        };
      },
    };
  }

  /**
   * Charge a booking: create intent → confirm → create booking row.
   */
  async function payAndBook(payload, mountEl) {
    const { enabled, stripe } = await getStripe();
    if (!enabled) {
      throw new Error('Stripe is not configured.');
    }

    const intentRes = await api('/api/stripe/booking-intent', {
      method: 'POST',
      body: JSON.stringify(payload),
    });

    const elements = stripe.elements({
      clientSecret: intentRes.data.client_secret,
      appearance: { theme: 'stripe' },
    });
    const paymentElement = elements.create('payment');
    mountEl.innerHTML = '';
    paymentElement.mount(mountEl);

    return {
      amount: intentRes.data.amount,
      platform_fee: intentRes.data.platform_fee,
      payment_intent_id: intentRes.data.payment_intent_id,
      async confirm() {
        const result = await stripe.confirmPayment({
          elements,
          redirect: 'if_required',
          confirmParams: { return_url: window.location.href },
        });
        if (result.error) {
          throw new Error(result.error.message || 'Payment failed.');
        }
        const booking = await api('/api/stripe/confirm-booking', {
          method: 'POST',
          body: JSON.stringify({
            ...payload,
            payment_intent_id: intentRes.data.payment_intent_id,
          }),
        });
        return booking;
      },
    };
  }

  window.CoachNowStripe = {
    loadConfig,
    getStripe,
    saveCard,
    payAndBook,
    enabled: async () => {
      const config = await loadConfig();
      return Boolean(config.enabled);
    },
  };
})();
